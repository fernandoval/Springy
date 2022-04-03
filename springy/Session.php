<?php
/**
 * Session treatment class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.2.1
 */

namespace Springy;

class Session
{
    /** @var bool session started control */
    private static $started = false;
    /** @var string|null the session id */
    private static $sid;
    /** @var string session engine type */
    private static $type;
    /** @var string session cookie name */
    private static $name;
    /** @var int session expiration time */
    private static $expires;

    /** @var string MemcacheD server address */
    private static $mcAddr;
    /** @var int MemcacheD server port */
    private static $mcPort;

    /** @var string database name */
    private static $database;
    /** @var string sessions table name */
    private static $dbTable;

    /** @var array the session data */
    private static $data = [];

    private const SESS_KEY = '_ffw_';

    // Session type constants
    public const ST_STANDARD = 'file';
    public const ST_MEMCACHED = 'memcached';
    public const ST_DATABASE = 'database';

    /**
     * Validates the session engine configuration.
     *
     * @param string|null $engine
     *
     * @throws Exception
     *
     * @return void
     */
    private static function validatEngine(?string $engine)
    {
        if (!isset($engine)) {
            throw new \Exception('Undefined session type.', 500);
        } elseif (
            !in_array(
                $engine,
                [self::ST_STANDARD, self::ST_MEMCACHED, self::ST_DATABASE],
                true
            )
        ) {
            throw new \Exception('Invalid session type.', 500);
        }

        self::$type = $engine;
    }

    /**
     * Loads the session configurations.
     */
    private static function confLoad()
    {
        $config = config_get('system.session');
        self::validatEngine($config['type'] ?? null);

        if (self::$type === self::ST_MEMCACHED) {
            self::$mcAddr = $config['memcached']['address'] ?? '127.0.0.1';
            self::$mcPort = $config['memcached']['port'] ?? 11211;
        } elseif (self::$type === self::ST_DATABASE) {
            self::$database = $config['database']['server'] ?? 'default';
            self::$dbTable = $config['database']['table'] ?? '_sessions';
        }

        self::$expires = $config['expires'] ?? 120;
        self::$name = $config['name'] ?? 'SPRINGYSID';
    }

    /**
     * Starts the session ID for MemcacheD or dabasese stored sessions.
     */
    private static function startSessionID()
    {
        if (is_null(self::$sid)) {
            if (!(self::$sid = Cookie::get(session_name()))) {
                self::$sid = substr(md5(uniqid(mt_rand(), true)), 0, 26);
            }
        }

        Cookie::set(
            session_name(),
            self::$sid,
            0,
            '/',
            config_get('system.session.domain'),
            config_get('system.session.secure'),
            true
        );
    }

    /**
     * Starts the database stored session.
     */
    private static function startDBSession()
    {
        self::startSessionID();

        // Expires old sessions
        $db = new DB(self::$database);
        $exp = self::$expires;
        if ($exp == 0) {
            $exp = 86400;
        }
        $db->execute(
            'DELETE FROM ' . self::$dbTable . ' WHERE `updated_at` <= ?',
            [date('Y-m-d H:i:s', time() - ($exp * 60))]
        );

        // Load from database
        $db->execute('SELECT `session_value` FROM ' . self::$dbTable . ' WHERE id = ?', [self::$sid]);
        if ($db->affectedRows()) {
            $res = $db->fetchNext();
            self::$data = unserialize($res['session_value']);
        } else {
            $sql = 'INSERT INTO ' . self::$dbTable . '(`id`, `session_value`, `updated_at`) VALUES (?, NULL, NOW())';
            $db->execute($sql, [self::$sid]);
            self::$data = [];
        }

        register_shutdown_function(['\Springy\Session', '_saveDbSession']);
        self::$started = true;
    }

    /**
     * Starts the Memcached stored session.
     */
    private static function startMCSession()
    {
        self::startSessionID();

        $memcached = new \Memcached();
        $memcached->addServer(self::$mcAddr, self::$mcPort);

        if (!(self::$data = $memcached->get('session_' . self::$sid))) {
            self::$data = [];
        }

        register_shutdown_function(['\Springy\Session', '_saveMcSession']);
        self::$started = true;
    }

    /**
     * Starts the session engine.
     */
    public static function start($name = null)
    {
        if (self::$started) {
            return true;
        }

        self::confLoad();

        if (!is_null($name)) {
            self::$name = $name;
        }
        session_name(self::$name);

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

        switch (self::$type) {
            case self::ST_MEMCACHED:
                self::startMCSession();
                break;
            case self::ST_DATABASE:
                self::startDBSession();
                break;
            default:
                session_set_cookie_params(
                    0,
                    '/',
                    config_get('system.session.domain'),
                    config_get('system.session.secure'),
                    true
                );
                self::$started = session_start();
                self::$data = $_SESSION[self::SESS_KEY] ?? [];
                self::$sid = session_id();
        }

        return self::$started;
    }

    /**
     * Saves the session data in database table.
     */
    public static function _saveDbSession()
    {
        $value = serialize(self::$data);
        $sql = 'UPDATE `' . self::$dbTable . '` SET `session_value` = ?, `updated_at` = NOW() WHERE `id` = ?';
        $db = new DB();
        $db->execute($sql, [$value, self::$sid]);
    }

    /**
     * Saves session data in Memcached service.
     */
    public static function _saveMcSession()
    {
        $memcached = new \Memcached();
        $memcached->addServer(self::$mcAddr, self::$mcPort);
        $memcached->set('session_' . self::$sid, self::$data, self::$expires * 60);
    }

    /**
     * Define o id da sessão.
     */
    public static function setSessionId($sid)
    {
        self::$sid = $sid;
        if (self::$type != self::ST_DATABASE) {
            session_id($sid);
        }
    }

    /**
     * Informa se a variável de sessão está definida.
     */
    public static function defined($var)
    {
        self::start();

        return isset(self::$data[$var]);
    }

    /**
     * Coloca um valor em variável de sessão.
     */
    public static function set($var, $value)
    {
        self::start();
        self::$data[$var] = $value;
        if (self::$type == self::ST_STANDARD) {
            $_SESSION[self::SESS_KEY][$var] = $value;
        }
    }

    /**
     * Pega o valor de uma variável de sessão.
     */
    public static function get($var)
    {
        self::start();

        return self::$data[$var] ?? null;
    }

    /**
     * Retorna todos os dados armazenados na sessão.
     */
    public static function getAll()
    {
        self::start();

        if (!empty(self::$data)) {
            return self::$data;
        }
    }

    /**
     * Pega o ID da sessão.
     */
    public static function getId()
    {
        self::start();

        return self::$sid;
    }

    /**
     * Remove uma variável de sessão.
     */
    public static function unregister($var)
    {
        self::start();
        unset(self::$data[$var]);
        if (
            self::$type === self::ST_STANDARD
            && isset($_SESSION)
            && isset($_SESSION[self::SESS_KEY])
            && isset($_SESSION[self::SESS_KEY][$var])
        ) {
            unset($_SESSION[self::SESS_KEY][$var]);
        }
    }
}
