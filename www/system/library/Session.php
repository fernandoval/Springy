<?php
/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright	Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2016 Fernando Val\n
 *
 *	\brief		Classe para tratamento de sessão
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    1.5.16
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */
namespace FW;

/**
 *  \brief Classe para tratamento de sessão.
 *  
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Session
{
    /// Flag de controle de sessão iniciada
    private static $started = false;
    /// ID da sessão
    private static $id = null;
    /// Flag de tipo de sessão
    private static $type = 'std';
    /// Endereço do servidor de sessão
    private static $server = null;
    /// Porta do servidor de sessão
    private static $port = null;
    /// Nome da tabela de sessão
    private static $session_table = null;
    /// Nome da coluna do session_id
    private static $id_column = null;
    /// Nome da coluna do valor da sessão
    private static $value_column = null;
    /// Nome da coluna da data de atualização da sessão
    private static $update_column = null;
    /// Dados da sessão
    private static $data = [];

    /**
     *	\brief Inicia a sessão.
     */
    public static function start($name = null)
    {
        if (self::$started) {
            return true;
        }

        // Carrega as configurações de tratamento de sesssão
        self::$type = Configuration::get('session', 'type');
        self::$server = Configuration::get('session', 'server_addr');
        self::$port = Configuration::get('session', 'server_port');
        self::$session_table = Configuration::get('session', 'table_name');
        self::$id_column = Configuration::get('session', 'id_column');
        self::$value_column = Configuration::get('session', 'value_column');
        self::$update_column = Configuration::get('session', 'update_column');

        if (!is_null($name)) {
            session_name($name);
        }

        // Verifica se há um cookie com o nome de sessão setado
        if ($id = Cookie::get(session_name())) {
            // Verifica se há algum caracter inválido no id da sessão
            if (preg_match('/([^A-Za-z0-9\-]+)/', $id)) {
                $id = substr(md5(uniqid(mt_rand(), true)), 0, 26);
                session_id($id);
            }
        } else {
            // Se não há um cookie ou o cookie está vazio, apaga o cookie por segurança
            Cookie::delete(session_name());
        }

        // Verifica se a sessão está em banco
        if (self::$type == 'db' || self::$type == 'memcached') {
            if (is_null(self::$id)) {
                if (Cookie::get(session_name())) {
                    self::$id = Cookie::get(session_name());
                } else {
                    // self::$id = md5(time().microtime().rand(1,9999999999999999999999999));
                    self::$id = substr(md5(uniqid(mt_rand(), true)), 0, 26);
                }
            }
            Cookie::set(session_name(), self::$id, 0, '/', Configuration::get('session', 'master_domain'), false, false);

            if (self::$type == 'db') {
                // Expira as sessões antigas
                $db = new DB();
                $exp = Configuration::get('session', 'expires');
                if ($exp == 0) {
                    $exp = 86400;
                }
                $db->execute('DELETE FROM '.self::$session_table.' WHERE '.self::$update_column.' <= ?', [date('Y-m-d H:i:s', time() - ($exp * 60))]);

                // Carrega a sessão do banco
                $db->execute('SELECT '.self::$value_column.' FROM '.self::$session_table.' WHERE '.self::$id_column.' = ?', [self::$id]);
                if ($db->affectedRows()) {
                    $res = $db->fetchNext();
                    self::$data = unserialize($res[self::$value_column]);
                } else {
                    $sql = 'INSERT INTO '.self::$session_table.'('.self::$id_column.','.self::$value_column.','.self::$update_column.')'
                         .' VALUES (?, NULL, NOW())';
                    $db->execute($sql, [self::$id]);
                    self::$data = [];
                }
            } else {
                $mc = new Memcached();
                $mc->addServer(self::$server, self::$port);

                if (!(self::$data = $mc->get('session_'.self::$id))) {
                    self::$data = [];
                }
            }
            register_shutdown_function(['\FW\Session', '_save_db_session']);
            // self::_save_db_session();

            self::$started = true;
        } else {
            session_set_cookie_params(0, '/', Configuration::get('session', 'master_domain'), false, false);
            self::$started = session_start();
            self::$data = isset($_SESSION['_ffw_']) ? $_SESSION['_ffw_'] : [];
            self::$id = session_id();
        }

        return self::$started;
    }

    /**
     *	\brief Salva uma sessão em banco ou memcached.
     */
    public static function _save_db_session()
    {
        if (self::$type == 'db') {
            $data_value = serialize(self::$data);
            $sql = 'UPDATE '.self::$session_table
                 .' SET '
                 .self::$value_column.' = ?, '
                 .self::$update_column.' = NOW()'
                 .' WHERE '
                 .self::$id_column.' = ?';
            $db = new DB();
            $db->execute($sql, [$data_value, self::$id]);
        } elseif (self::$type == 'memcached') {
            $mc = new Memcached();
            $mc->addServer(self::$server, self::$port);
            $mc->set('session_'.self::$id, self::$data, Configuration::get('session', 'expires') * 60);
        }
    }

    /**
     *  \brief Define o id da sessão.
     */
    public static function setSessionId($id)
    {
        self::$id = $id;
        if (self::$type != 'db') {
            session_id($id);
        }
    }

    /**
     *	\brief Informa se a variável de sessão está definida.
     */
    public static function defined($var)
    {
        self::start();

        return isset(self::$data[$var]);
    }

    /**
     *	\brief Coloca um valor em variável de sessão.
     */
    public static function set($var, $value)
    {
        self::start();
        self::$data[$var] = $value;
        if (self::$type == 'std') {
            $_SESSION['_ffw_'][$var] = $value;
        } else {
            // self::_save_db_session();
        }
    }

    /**
     *	\brief Pega o valor de uma variável de sessão.
     */
    public static function get($var)
    {
        self::start();
        // if (isset($_SESSION['_ffw_'][$var])) {
            // return $_SESSION['_ffw_'][$var];
        // }
        if (isset(self::$data[$var])) {
            return self::$data[$var];
        }

        return;
    }

    /**
     *	\brief Retorna todos os dados armazenados na sessão.
     *
     *	\return retorna \c array() se tiver sucesso ou \c NULL se não houver sessão
     */
    public static function getAll()
    {
        self::start();
        // if (isset($_SESSION['_ffw_'])) {
            // return $_SESSION['_ffw_'];
        // }
        if (!empty(self::$data)) {
            return self::$data;
        }

        return;
    }

    /**
     *	\brief Pega o ID da sessão.
     *
     *	\return retorna o ID da sessão ativa
     */
    public static function getId()
    {
        self::start();
        // return session_id();
        return self::$id;
    }

    /**
     *	\brief Remove uma variável de sessão.
     */
    public static function unregister($var)
    {
        self::start();
        unset(self::$data[$var]);
        if (self::$type == 'std' && isset($_SESSION) && isset($_SESSION['_ffw_']) && isset($_SESSION['_ffw_'][$var])) {
            unset($_SESSION['_ffw_'][$var]);
        } else {
            // self::_save_db_session();
        }
    }
}
