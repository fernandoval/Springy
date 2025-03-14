<?php

/**
 * Session treatment class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.3.0
 */

namespace Springy;

use Memcached;
use Springy\Exceptions\SpringyException;

class Session
{
    private const SESS_KEY = '_ffw_';

    // Session type constants
    public const ST_STANDARD = 'file';
    public const ST_MEMCACHED = 'memcached';
    public const ST_DATABASE = 'database';

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

    /** @var array the session data */
    private static $data = [];

    private static function createSessId(): string
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, 26);
    }

    private static function deleteFromDB(): void
    {
        $sql = 'DELETE FROM `' . self::sessionTableName() . '` WHERE `id` = ?';
        $db = new DB();
        $db->execute($sql, [self::$sid]);
    }

    private static function deleteFromMC(): void
    {
        $memcached = self::memcached();
        $memcached->delete('session_' . self::$sid);
    }

    public static function destroy(): void
    {
        self::start();
        self::$data = [];

        // match (self::$type) {
        //     self::ST_STANDARD => session_destroy(),
        //     self::ST_MEMCACHED => self::deleteFromMC(),
        //     self::ST_DATABASE => self::deleteFromDB(),
        // };

        // if (self::$type === self::ST_STANDARD) {
        //     session_regenerate_id(true);
        //     self::$sid = session_id();

        //     return;
        // };

        // self::$sid = self::createSessId();
        // self::startSessionID();
        if (self::$type === self::ST_DATABASE) {
            self::deleteFromDB();
            self::$sid = self::createSessId();
            self::startSessionID();

            return;
        };

        session_regenerate_id(true);
        session_destroy();
        self::$sid = session_id();
    }

    private static function checkCookieId(): void
    {
        $sessName = Cookie::get(session_name());

        if ($sessName) {
            // Checks for an invalid char in the session name
            if (preg_match('/([^A-Za-z0-9\-]+)/', $sessName)) {
                session_id(self::createSessId());
            }

            return;
        }

        // Grants there is no invalid cookie.
        Cookie::delete(session_name());
    }

    /**
     * Validates the session engine configuration.
     *
     * @param string|null $engine
     *
     * @throws SpringyException
     *
     * @return void
     */
    private static function validatEngine(?string $engine): void
    {
        if (is_null($engine)) {
            throw new SpringyException('Undefined session type.');
        } elseif (
            !in_array(
                $engine,
                [self::ST_STANDARD, self::ST_MEMCACHED, self::ST_DATABASE],
                true
            )
        ) {
            throw new SpringyException('Invalid session type.');
        }

        self::$type = $engine;
    }

    /**
     * Loads the session configurations.
     */
    private static function confLoad(): void
    {
        $config = config_get('system.session');
        self::validatEngine($config['type'] ?? null);
        self::$expires = $config['expires'] ?? 120;
        self::$name = $config['name'] ?? 'SPRINGYSID';
    }

    /**
     * Starts the session ID for MemcacheD or dabasese stored sessions.
     */
    private static function startSessionID(): void
    {
        if (is_null(self::$sid)) {
            self::$sid = Cookie::get(session_name()) ?: self::createSessId();
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
    private static function startDBSession(): void
    {
        self::startSessionID();

        // Expires old sessions
        $db = new DB(config_get('system.session.database.server', 'default'));
        $exp = self::$expires;
        if ($exp == 0) {
            $exp = 86400;
        }
        $db->execute(
            'DELETE FROM ' . self::sessionTableName() . ' WHERE `updated_at` <= ?',
            [date('Y-m-d H:i:s', time() - ($exp * 60))]
        );

        // Load from database
        $db->execute('SELECT `session_value` FROM ' . self::sessionTableName() . ' WHERE id = ?', [self::$sid]);
        if ($db->affectedRows()) {
            $res = $db->fetchNext();
            self::$data = unserialize($res['session_value']);
        } else {
            $sql = 'INSERT INTO ' . self::sessionTableName() . '(`id`, `session_value`, `updated_at`) VALUES (?, NULL, NOW())';
            $db->execute($sql, [self::$sid]);
            self::$data = [];
        }

        register_shutdown_function(self::saveToDB(...));
        self::$started = true;
    }

    /**
     * Starts the Memcached stored session.
     */
    private static function startMCSession(): void
    {
        ini_set('session.save_handler', self::ST_MEMCACHED);
        ini_set(
            'session.save_path',
            config_get('system.session.memcached.address', '127.0.0.1')
            . ':' . config_get('system.session.memcached.port', 11211)
        );
        ini_set('memcached.sess_locking', 'Off');
        ini_set('memcached.sess_prefix', 'springy.sess.key.');
        self::startStdSession();

        // self::startSessionID();

        // $memcached = self::memcached();
        // self::$data = $memcached->get('session_' . self::$sid) ?: [];

        // register_shutdown_function(self::saveToMC(...));
        // self::$started = true;
    }

    private static function startStdSession(): void
    {
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

    /**
     * Starts the session engine.
     */
    public static function start($name = null): void
    {
        if (self::$started) {
            return;
        }

        self::confLoad();

        if (!is_null($name)) {
            self::$name = $name;
        }

        session_name(self::$name);
        self::checkCookieId();

        match (self::$type) {
            self::ST_DATABASE => self::startDBSession(),
            self::ST_MEMCACHED => self::startMCSession(),
            default => self::startStdSession()
        };
    }

    /**
     * Saves the session data in database table.
     *
     * @return void
     */
    private static function saveToDB(): void
    {
        $value = serialize(self::$data);
        $sql = 'UPDATE `' . self::sessionTableName() . '` SET `session_value` = ?, `updated_at` = NOW() WHERE `id` = ?';
        $db = new DB();
        $db->execute($sql, [$value, self::$sid]);
    }

    /**
     * Saves session data in Memcached service.
     *
     * @return void
     */
    private static function saveToMC(): void
    {
        $memcached = self::memcached();
        $memcached->set('session_' . self::$sid, self::$data, self::$expires * 60);
    }

    private static function sessionTableName(): string
    {
        return config_get('system.session.database.table', '_sessions');
    }

    /**
     * Define o id da sessão.
     */
    public static function setSessionId(string $sid): void
    {
        self::$sid = $sid;

        if (self::$type != self::ST_DATABASE) {
            session_id($sid);
        }
    }

    /**
     * Informa se a variável de sessão está definida.
     */
    public static function defined(string $var): bool
    {
        self::start();

        return isset(self::$data[$var]);
    }

    /**
     * Coloca um valor em variável de sessão.
     */
    public static function set(string $var, mixed $value): void
    {
        self::start();
        self::$data[$var] = $value;

        // if (self::$type == self::ST_STANDARD) {
        if (self::$type !== self::ST_DATABASE) {
            $_SESSION[self::SESS_KEY][$var] = $value;
        }
    }

    /**
     * Pega o valor de uma variável de sessão.
     */
    public static function get(string $var): mixed
    {
        self::start();

        return self::$data[$var] ?? null;
    }

    /**
     * Retorna todos os dados armazenados na sessão.
     */
    public static function getAll(): array
    {
        self::start();

        return self::$data;
    }

    /**
     * Pega o ID da sessão.
     */
    public static function getId(): string
    {
        self::start();

        return self::$sid;
    }

    private static function memcached(): Memcached
    {
        $mcd = new Memcached();
        $mcd->addServer(
            config_get('system.session.memcached.address', '127.0.0.1'),
            config_get('system.session.memcached.port', 11211)
        );

        return $mcd;
    }

    /**
     * Remove uma variável de sessão.
     */
    public static function unregister($var): void
    {
        self::start();
        unset(self::$data[$var]);

        if (
            self::$type !== self::ST_DATABASE
            && isset($_SESSION)
            && isset($_SESSION[self::SESS_KEY])
            && isset($_SESSION[self::SESS_KEY][$var])
        ) {
            unset($_SESSION[self::SESS_KEY][$var]);
        }
    }
}
