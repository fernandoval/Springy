<?php
/** \file
 *  Springy.
 *
 *  \brief      Script da classe de acesso a banco de dados.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    1.7.29
 *  \ingroup    framework
 */
namespace Springy;

/**
 *  \brief Classe para acesso a banco de dados.
 *
 *  Esta classe é dinâmica, porém alguns de seus controles são estáticos.
 *
 *	\note Esta classe usa a PHP Data Object (PDO) para acesso a banco de dados.
 */
class DB
{
    /// Guarda os IDs de conexão com os SGBDs
    private static $conectionIds = [];
    /// SQL Resource
    private $resSQL = null;
    /// Tempo em segundos da validade do cache
    private $cacheExpires = null;
    /// Cache dos registros
    private $cacheStatement = null;
    /// Último comando executado
    private $lastQuery = '';
    /// Valores do Último comando executado
    private $lastValues = null;
    /// Código do erro ocorrido no execute
    private $sqlErrorCode = null;
    /// Informações do erro ocorrido no execute
    private $sqlErrorInfo = null;
    /// Contador de comandos SQL executados
    private static $sqlNum = 0;
    /// Recurso de conexão atual
    private $dataConnect = false;
    /// Entrada de configuração de banco atual
    private $database = false;
    /// Flag de habilitação do relatório de erros
    private $reportError = true;
    /// Flag do modo debug
    private static $dbDebug = false;
    /// Controle de falhas de conexão
    private static $conErrors = [];

    /**
     *  \brief Método construtor da classe.
     *
     *  Cria uma instância da classe a inicializa a conexão com o banco de dados
     *
     *  \param $database chave de configuração do banco de dados.
     *    Default = 'default'
     *  \param $cache_expires tempo em segundo de cacheamento de consultas.
     *    Default = null (sem cache)
     */
    public function __construct($database = 'default', $cacheExpires = null)
    {
        $this->cacheExpires = $cacheExpires;
        $this->database = $database;
        $this->dataConnect = $this->connect($this->database);
    }

    /**
     *  \brief Método destrutor da classe.
     *
     *  Fecha todos os cursores, consultas em aberto com banco de dados e desinstancia a classe
     */
    public function __destruct()
    {
        if (!is_null($this->resSQL)) {
            $this->resSQL->closeCursor();
            $this->resSQL = null;
        }
    }

    /**
     *  \brief Conecta ao banco de dados.
     *
     *  @param $database chave de configuração do banco de dados.
     *    Default = 'default'
     *
     *  @return Retorna o conector do banco de dados
     */
    public function connect($database)
    {
        if (isset(self::$conErrors[$database])) {
            return false;
        }

        // Verifica se a instância já está definida e conectada
        if (isset(self::$conectionIds[$database])) {
            return self::$conectionIds[$database]['con'];
        }

        // Lê as configurações de acesso ao banco de dados
        $conf = Configuration::get('db', $database);

        // Verifica se o servidor é um pool (round robin)
        if ($conf['database_type'] == 'pool' && is_array($conf['host_name'])) {
            return $this->_round_robin($database, $conf);
        }

        $pdoConf = [];
        if ($conf['database_type'] == 'mysql') {
            $pdoConf[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES \'UTF8\'';
        }

        if ($conf['persistent']) {
            $pdoConf[ \PDO::ATTR_PERSISTENT ] = true;
        }

        /*
         *  A variável abaixo é setada pois caso a conexão com o banco falhe, o callback de erro será chamado e a variável já estará setada.
         *  Caso a conexão seja feita com sucesso, a variavel é removida.
         */
        self::$conErrors[$database] = true;

        if (!$conf['host_name'] || !$conf['database']) {
            $this->reportError('HostName / DataBase not defined.');
        }

        //	a instância de conexão é estática, para nao criar uma nova a cada nova instãncia da classe
        try {
            self::$conectionIds[$database] = [
                'con' => new \PDO(
                    $conf['database_type'].':host='.$conf['host_name'].';dbname='.$conf['database'],
                    $conf['user_name'],
                    $conf['password'],
                    $pdoConf
                ),
                'dbName' => $database,
            ];
            unset($pdoConf, self::$conErrors[$database]);
        } catch (\PDOException $error) {
            $callers = debug_backtrace();
            if (!isset($callers[1]) || $callers[1]['class'] != 'Springy\Errors' || $callers[1]['function'] != 'sendReport') {
                Errors::errorHandler((int) $error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine(), null);
            }
        }

        return self::$conectionIds[$database]['con'];
    }

    /**
     *	\brief Método privado de controle de round robin de conexão.
     *
     *	Define o próximo servidor do pool de SGBDs.
     *
     *	@param $database chave de configuração do banco de dados.
     *	@param $dbconf entradas de configuração do banco de dados.
     *
     *	@return Retorna o conector do banco de dados.
     */
    private function _round_robin($database, $dbconf)
    {
        // Lê as configurações de controle de round robin
        $roundRobin = Configuration::get('db', 'round_robin');

        // Efetua controle de round robin por Memcached
        if ($roundRobin['type'] == 'memcached') {
            $memCached = new \Memcached();
            $memCached->addServer($roundRobin['server_addr'], $roundRobin['server_port']);

            // Define o próximo servidor do pool
            if (!($actual = (int) $memCached->get('dbrr_'.$database))) {
                $actual = 0;
            }
            if (++$actual >= count($dbconf['host_name'])) {
                $actual = 0;
            }

            $memCached->set('dbrr_'.$database, $actual, 0);
        }
        // Efetua controle de round robin em arquivo
        elseif ($roundRobin['type'] == 'file') {
            // Define o próximo servidor do pool
            if ((!file_exists($roundRobin['server_addr'].DIRECTORY_SEPARATOR.'dbrr_'.$database)) || !($actual = (int) file_get_contents($roundRobin['server_addr'].DIRECTORY_SEPARATOR.'dbrr_'.$database))) {
                $actual = 0;
            }
            if (++$actual >= count($dbconf['host_name'])) {
                $actual = 0;
            }

            file_put_contents($roundRobin['server_addr'].DIRECTORY_SEPARATOR.'dbrr_'.$database, $actual);
        } else {
            return false;
        }

        // Tenta conectar ao banco e retorna o resultado
        self::$conectionIds[$database] = [
            'con'    => $this->connect($dbconf['host_name'][$actual]),
            'dbName' => $dbconf['host_name'][$actual],
        ];

        return self::$conectionIds[$database]['con'];
    }

    /**
     *	\brief Método para verificar se a conexão com o banco foi feita.
     *
     *	@param $database chave de configuração do banco de dados.
     *		Default = 'default'
     */
    public static function connected($database = 'default')
    {
        return !isset(self::$conErrors[$database]) && isset(self::$conectionIds[$database]) && self::$conectionIds[$database]['con'];
    }

    /**
     *  \brief DEPRECATED - Use connected
     *  \deprecated
     *  \see connected.
     */
    public static function hasConnection()
    {
        throw new \Exception('Deprecated method');
        // return self::connected($database);
    }

    /**
     *	\brief Fecha a conexão com o banco de dados.
     */
    public function disconnect()
    {
        if (static::connected($this->database)) {
            ///	a instância de conexão é estática, para não criar uma nova a cada nova instância da classe
            unset(self::$conectionIds[$this->database]['con']);
            $this->dataConnect = false;
        }
        unset(self::$conectionIds[$this->database]);
    }

    /**
     *  \brief The status of error report.
     *  \param $status - if defined set the report of errors on (true) or off (false).
     *  \return (bool) the current status.
     */
    public function errorReportStatus($status = null)
    {
        if (is_bool($status)) {
            $this->reportError = $status;
        }

        return $this->reportError;
    }

    /**
     *  \brief Desabilita o report de erros.
     *  \deprecated
     *  \see errorReportStatus.
     *  \return (bool) the current status.
     */
    public function disableReportError()
    {
        return $this->errorReportStatus(false);
    }

    /**
     *  \brief Habilita o report de erros.
     *  \deprecated
     *  \see errorReportStatus.
     *  \return (bool) the current status.
     */
    public function enableReportError()
    {
        return $this->errorReportStatus(true);
    }

    /**
     *	\brief Send the error occurrency to the Webmaster.
     */
    private function reportError($msg, \PDOException $exception = null)
    {
        if (!$this->reportError) {
            return;
        }
        // Read the database access configurations
        $conf = Configuration::get('db', $this->database);

        if (isset($this->lastQuery)) {
            if (PHP_SAPI === 'cli' || defined('STDIN')) {
                $sqlError = htmlentities((is_object($this->lastQuery) ? $this->lastQuery->__toString() : $this->lastQuery))."\n".'Parametros: '.Kernel::print_rc($this->lastValues);
            } else {
                $sqlError = '<pre>'.htmlentities((is_object($this->lastQuery) ? $this->lastQuery->__toString() : $this->lastQuery)).'</pre><br /> Parametros:<br />'.Kernel::print_rc($this->lastValues);
            }
        } else {
            $sqlError = 'Still this connection was not executed some instruction SQL using.';
        }

        if ($this->resSQL) {
            $errorInfo = $this->resSQL->errorInfo();
        } elseif ($this->dataConnect) {
            $errorInfo = $this->dataConnect->errorInfo();
        } else {
            $errorInfo = [0, 0, 'Erro desconhecido'];
        }

        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            $htmlError = 'Dados do banco'."\n"
                       .'Host: '.$conf['host_name']."\n"
                       .'Login: '.(isset($conf['user_name']) ? $conf['user_name'] : 'não informado')."\n"
                       .'Senha: '.(isset($conf['password']) ? $conf['password'] : 'não informado')."\n"
                       .'DB: '.(isset($conf['database']) ? $conf['database'] : 'não informado')."\n";
        } else {
            $htmlError = '<tr>'
                       .'  <td style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px" colspan="2">Dados do banco</td>'
                       .'</tr>'
                       .'<tr>'
                       .'  <td valign="top"><label style="font-weight:bold">Host:</label></td>'
                       .'  <td>'.$conf['host_name'].'</td>'
                       .'</tr>'
                       .'<tr style="background:#efefef">'
                       .'  <td valign="top"><label style="font-weight:bold">User:</label></td>'
                       .'  <td><span style="background:#efefef">'.(isset($conf['user_name']) ? $conf['user_name'] : 'não informado').'</span></td>'
                       .'</tr>'
                       .'<tr>'
                       .'  <td valign="top"><label style="font-weight:bold">Pass:</label></td>'
                       .'  <td>'.(isset($conf['password']) ? $conf['password'] : 'não informado').'</td>'
                       .'</tr>'
                       .'<tr>'
                       .'  <td valign="top"><label style="font-weight:bold">DB:</label></td>'
                       .'  <td><span style="background:#efefef">'.(isset($conf['database']) ? $conf['database'] : 'não informado').'</span></td>'
                       .'</tr>';
        }
        unset($sqlError);

        // Send the report of error and kill the application
        Errors::sendReport(
            '<span style="color:#FF0000">'.$msg.'</span> - '.'('.$errorInfo[1].') '.$errorInfo[2].($exception ? '<br />'.$exception->getMessage() : '').'<br /><pre>'.$this->lastQuery.'</pre><br />Valores: '.Kernel::print_rc($this->lastValues),
            500,
            hash('crc32', $msg.$errorInfo[1].$this->lastQuery), // error id
            $htmlError
        );
    }

    /**
     *	\brief Alterna o estado de debug de banco de dados.
     */
    public static function debug($debug)
    {
        self::$dbDebug = $debug;
    }

    /**
     *	\brief Inicializa uma transação.
     *
     *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
     */
    public static function beginTransaction($database = 'default')
    {
        self::connect($database)->beginTransaction();
    }

    /**
     *	\brief Cancela as alterações e termina a transação.
     *
     *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
     */
    public static function rollBack($database = 'default')
    {
        self::connect($database)->rollBack();
    }

    /**
     *	\brief Confirma as alterações e termina a transação.
     *
     *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
     */
    public static function commit($database = 'default')
    {
        self::connect($database)->commit();
    }

    /**
     *	\brief Cancela todas as transações ativas.
     *
     *	Em caso de erro no php, executa um all roll back.
     *
     *	Como as transações podem utilzar varias classes e métodos, a transação será "estatica"
     */
    public static function rollBackAll()
    {
        foreach (self::$conectionIds as $database) {
            if ($database['con']->inTransaction()) {
                $database['con']->rollBack();
            }
        }
    }

    /**
     *  \brief DEPRECATED - Use rollBackAll()
     *  \deprecated
     *  \see rollBackAll.
     */
    public static function transactionAllRollBack()
    {
        self::rollBackAll();
    }

    /**
     *  Executa uma consulta no banco de dados.
     *
     *  \param $sql String contendo comando SQL a ser executado
     *  \param $where_v array contendo parâmetros para execução do comando
     *  \param $cache_expires tempo em segundos para cacheamento da consulta.
     *    Default = null (sem cache)
     */
    public function execute($sql, array $where_v = [], $cache_expires = null)
    {
        $this->sqlErrorCode = null;
        $this->sqlErrorInfo = null;
        self::$sqlNum++;

        // Verifica se está sendo usado o recurso de contagem de linhas encontrados da última consulta do MySQL e cria um comando único
        if ((is_int($this->cacheExpires) || is_int($cache_expires)) && strtoupper(substr(ltrim($sql), 0, 19)) == 'SELECT FOUND_ROWS()' && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT ') {
            $this->lastQuery = $sql.'; /* '.md5(implode('//', array_merge([$this->lastQuery], $this->lastValues))).' */';
        } else {
            $this->lastQuery = $sql;
        }

        if (($sql instanceof DBSelect) || ($sql instanceof DBInsert) || ($sql instanceof DBUpdate) || ($sql instanceof DBDelete)) {
            $this->lastValues = $sql->getAllValues();
        } else {
            $this->lastValues = $where_v;
            $where_v = [];
        }

        $sql = null;

        // Recupera a configuração de cache
        $dbcache = (Configuration::get('db', 'cache'));
        // Limpa o que estiver em memória e tiver sido carregado de cache
        $this->cacheStatement = null;
        // Configuração de cache está ligada?
        if (is_array($this->lastValues) && is_array($dbcache) && isset($dbcache['type']) && $dbcache['type'] == 'memcached') {
            $cacheKey = md5(implode('//', array_merge([$this->lastQuery], $this->lastValues)));
            $this->resSQL = null;
            // O comando é um SELECT e é para guardar em cache?
            if ((is_int($this->cacheExpires) || is_int($cache_expires)) && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT ') {
                try {
                    $mc = new \Memcached();
                    $mc->addServer($dbcache['server_addr'], $dbcache['server_port']);
                    if ($sql = $mc->get('cacheDB_'.$cacheKey)) {
                        $this->cacheStatement = $sql;
                    }
                    unset($mc);
                } catch (Exception $e) {
                    $this->cacheStatement = null;
                }
            }
        }

        // Se o resultado não foi pego do cache, consulta o banco
        if (is_null($this->cacheStatement)) {
            if (($this->resSQL = $this->dataConnect->prepare($this->lastQuery)) === false) {
                $this->sqlErrorCode = $this->resSQL->errorCode();
                $this->sqlErrorInfo = $this->resSQL->errorInfo();
                $this->reportError('Can\'t prepare query.');

                return false;
            }

            if (count($this->lastValues)) {
                $numeric = 0;

                foreach ($this->lastValues as $key => $where) {
                    switch (gettype($where)) {
                        case 'boolean':
                            $param = \PDO::PARAM_BOOL;
                        break;
                        case 'integer':
                            $param = \PDO::PARAM_INT;
                        break;
                        case 'NULL':
                            $param = \PDO::PARAM_NULL;
                        break;
                        default:
                            $param = \PDO::PARAM_STR;
                        break;
                    }

                    if (is_numeric($key)) {
                        $this->resSQL->bindValue(++$numeric, $where, $param);
                    } else {
                        $this->resSQL->bindValue(':'.$key, $where, $param);
                    }
                }
                unset($key, $where, $param, $numeric);
            }

            $this->resSQL->closeCursor();
            if ($this->resSQL->execute() === false) {
                $this->sqlErrorCode = $this->resSQL->errorCode();
                $this->sqlErrorInfo = $this->resSQL->errorInfo();
                $this->reportError('Can\'t execute query.');

                return false;
            }

            // Configuração de cache está ligada?
            if (is_array($dbcache) && isset($dbcache['type']) && $dbcache['type'] == 'memcached') {
                // O comando é um SELECT e é para guardar em cache?
                if ((is_int($this->cacheExpires) || is_int($cache_expires)) && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT ') {
                    try {
                        $mc = new \Memcached();
                        $mc->addServer($dbcache['server_addr'], $dbcache['server_port']);
                        $this->cacheStatement = $this->get_all();
                        $mc->set('cacheDB_'.$cacheKey, $this->cacheStatement, min(is_int($cache_expires) ? $cache_expires : 86400, is_int($this->cacheExpires) ? $this->cacheExpires : 86400));
                        unset($mc);
                        $this->resSQL->closeCursor();
                        $this->resSQL = null;
                    } catch (Exception $e) {
                        Kernel::debug($this->lastQuery, 'Erro: '.$e->getMessage());
                    }
                }
            }
        }

        if (self::$dbDebug || Configuration::get('system', 'sql_debug')) {
            $conf = Configuration::get('db', self::$conectionIds[$this->database]['dbName']);

            Kernel::debug(
                '<pre>'.
                    $this->lastQuery.
                '</pre><br />Valores: '.Kernel::print_rc($this->lastValues).'<br />'.
                'Affected Rows: '.$this->affectedRows().'<br />'.
                'DB: '.(isset($conf['database']) ? $conf['database'] : 'não informado'), 'SQL #'.self::$sqlNum, false);
        }

        return true;
    }

    /**
     *	\brief Retorna o último comando executado.
     */
    public function lastQuery()
    {
        return $this->lastQuery;
    }

    /**
     *  \brief Retorna uma string com o código do último erro ocorrido com a instância do banco.
     */
    public function errorCode()
    {
        return $this->dataConnect->errorCode();
    }

    /**
     *  \brief Retorna um array com informações do último erro ocorrido com a instãncia do banco.
     */
    public function errorInfo()
    {
        return $this->dataConnect->errorInfo();
    }

    /**
     *  \brief Retorna uma string com o código do erro ocorrido com o último \c execute
     *  \see execute.
     */
    public function statmentErrorCode()
    {
        return $this->sqlErrorCode;
    }

    /**
     *  \brief Retorna um array com informações do erro ocorrido com o último \c execute
     *  \see execute.
     */
    public function statmentErrorInfo()
    {
        return $this->sqlErrorInfo;
    }

    /**
     *	\brief Pega o nome do driver do banco.
     *
     *	\return Retorna uma string contendo o nome do driver do banco de dados atual
     */
    public function driverName()
    {
        return $this->dataConnect->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     *  \brief Pega algumas informações a respeito da versão do servidor.
     *
     *  \return Retorna um valor inteiro com a versão do servidor
     */
    public function serverVersion()
    {
        return $this->dataConnect->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     *	\brief Retorna o valor do campo autoincremento do último INSERT.
     */
    public function lastInsertedId($indice = '')
    {
        return $this->dataConnect->lastInsertId(((!$indice && $this->lastQuery instanceof DBInsert) ? $this->lastQuery->getTable().'_id_seq' : $indice));
    }

    /**
     *	\brief Retorna o número de linhas afetadas no último comando.
     */
    public function affectedRows()
    {
        return $this->resSQL->rowCount();
    }

    /**
     *  \brief DEPRECATED - Use affectedRows()
     *  \deprecated
     *  \see affectedRows.
     */
    public function num_rows()
    {
        if (is_null($this->cacheStatement)) {
            return $this->affected_rows();
        }

        return count($this->cacheStatement);
    }

    /**
     *	\brief Retorna todas as linhas do resultado de uma consulta.
     */
    public function fetchAll($resultType = \PDO::FETCH_ASSOC)
    {
        if (is_null($this->cacheStatement)) {
            if ($this->resSQL) {
                return $this->resSQL->fetchAll($resultType);
            }
        } else {
            return $this->cacheStatement;
        }

        return false;
    }

    /**
     *  \brief DEPRECATED - Use fetch_all
     *  \deprecated
     *  \see fetch_all.
     */
    public function get_all($resultType = \PDO::FETCH_ASSOC)
    {
        return $this->fetchAll($resultType);
    }

    /**
     *	\brief Retorna o primeiro resultado do cursor de uma consulta.
     */
    public function fetchFirst($resultType = \PDO::FETCH_ASSOC)
    {
        if (is_null($this->cacheStatement)) {
            if ($this->resSQL) {
                return $this->resSQL->fetch($resultType, \PDO::FETCH_ORI_FIRST);
            }
        } else {
            return reset($this->cacheStatement);
        }

        return false;
    }

    /**
     *	\brief Retorna o resultado anterior do cursor de uma consulta.
     */
    public function fetchPrev($resultType = \PDO::FETCH_ASSOC)
    {
        if (is_null($this->cacheStatement)) {
            if ($this->resSQL) {
                return $this->resSQL->fetch($resultType, \PDO::FETCH_ORI_PRIOR);
            }
        } else {
            return prev($this->cacheStatement);
        }

        return false;
    }

    /**
     *	\brief Retorna o próximo resultado de uma consulta.
     */
    public function fetchNext($resultType = \PDO::FETCH_ASSOC)
    {
        if (is_null($this->cacheStatement)) {
            if ($this->resSQL) {
                return $this->resSQL->fetch($resultType);
            }
        } else {
            if ($r = each($this->cacheStatement)) {
                return $r['value'];
            }
        }

        return false;
    }

    /**
     *	\brief Retorna o último resultado do cursor de uma consulta.
     */
    public function fetchLast($resultType = \PDO::FETCH_ASSOC)
    {
        if (is_null($this->cacheStatement)) {
            if ($this->resSQL) {
                return $this->resSQL->fetch($resultType, \PDO::FETCH_ORI_LAST);
            }
        } else {
            return end($this->cacheStatement);
        }

        return false;
    }

    /**
     *	\brief Retorna o valor de uma coluna do último registro pego por fetch_next.
     */
    public function getColumn($var = 0)
    {
        if (is_null($this->cacheStatement)) {
            if ($this->resSQL && is_numeric($var)) {
                return $this->resSQL->fetchColumn($var);
            }
        } else {
            if ($r = each($this->cacheStatement)) {
                return $r['value'][$var];
            }
        }

        $this->reportError($var.' is not defined in select (remember, it\'s a case sensitive) or $data is empty.');

        return false;
    }

    /**
     *	\brief Converte uma data do formato brasileiro para o formato universal.
     *
     *	@param $datetime String contendo a data e hora no formato brasileiro (d/m/Y H:n:s)
     *	@param $flgtime Interruptor de concatenação da hora após a data
     *
     *	@return Retorna a data no formato universal (Y-m-d) concatenada da hora no formato universal (H:n:s), se o $flgtime for TRUE.
     */
    public static function castDateBrToDb($datetime, $flgtime = false)
    {
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', $datetime);

        return $flgtime ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
    }

    /**
     *	\brief Converte uma data do formato universal para o formato brasileiro 24h.
     *
     *	@param $datetime String contendo a data e hora no formato universal (Y-m-d H:n:s)
     *	@param $flgtime Interruptor de concatenação da hora após a data
     *	@param $seconds Interruptor de concatenação dos segundos após a data
     *
     *	@return Retorna a data no formato brasileiro (d/m/Y) concatenada da hora no formato brasileiro 24h (H:n:s), se o $flgtime for TRUE.
     */
    public static function castDateDbToBr($datetime, $flgtime = false, $sec = false)
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);

        if (!$flgtime) {
            return $date->format('d/m/Y');
        }

        return $sec ? $date->format('d/m/Y H:i') : $date->format('d/m/Y H:i:s');
    }

    /**
     *  \brief Converte em UNIX timestamp o valor data + hora no formato universal.
     *
     *  \param (string)$dateTime - data hora no format Y-m-d H:i:s
     *
     *  \return Retorna o valor UNIX timestamp
     */
    public static function makeDbDateTime($dateTime)
    {
        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/', $dateTime)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime)->getTimestamp();
        } elseif (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})/', $dateTime)) {
            return \DateTime::createFromFormat('d/m/Y H:i:s', $dateTime)->getTimestamp();
        }

        return;
    }

    /**
     *  \brief DEPRECATED - Use makeDbDateTime()
     *  \deprecated
     *  \see makeDbDateTime.
     */
    public static function dateToTime($dateTime)
    {
        return self::makeDbDateTime($dateTime);
    }

    /**
     *  \brief Converte um valor datetime do banco em string de data brasileira.
     *
     *  \note Verificar real necessidade de manutenção desse método
     *  \param (string)$dataTimeStamp - data hora no format Y-m-d H:i:s
     *  \return Retorna uma string no formato '<dia> de <nome_do_mes>'.
     */
    public static function longBrazilianDate($dataTimeStamp)
    {
        $dateTime = static::makeDbDateTime($dataTimeStamp);
        $mes = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $numMes = (int) date('m', $dateTime);

        return date('d', $dateTime).' de '.$mes[--$numMes].' de '.date('Y', $dateTime);
    }

    /**
     *  \brief DEPRECATED - Use londBrazilianDate()
     *  \deprecated
     *  \see londBrazilianDate.
     */
    public static function dateToStr($dataTimeStamp)
    {
        return self::longBrazilianDate($dataTimeStamp);
    }
}
