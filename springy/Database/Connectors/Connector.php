<?php

/**
 * DBMS connector basic implementation.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Connectors;

use Closure;
use Exception;
use PDO;
use Springy\Exceptions\SpringyException;

/**
 * DBMS connector basic implementation.
 */
class Connector
{
    protected string $database; // name of the database
    protected int $retries; // connection tentative possible
    protected int $retrySleep; // sleep time in seconds between each try connection
    protected $encloseCharOpn = '`';
    protected $encloseCharCls = '`';
    protected Closure|null $onSuccessConnect; // callback function to execute when connection is successful
    /** @var array PDO constructor options */
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];
    protected string $password; // the database user password
    protected PDO|null $pdo;
    protected string $timezone; // timezone configuration
    protected string $username; // the database username

    public function __construct(array $settings)
    {
        $this->pdo = null;
        $this->onSuccessConnect = null;
        $this->retries = 0;
        $this->retrySleep = 0;
        $this->timezone = $settings['timezone'] ?? '';
        $this->setDatabase($settings['database'] ?? '');
        $this->setUsername($settings['username'] ?? '');
        $this->setPassword($settings['password'] ?? '');
    }

    /**
     * Connects with the database.
     */
    public function connect(): PDO
    {
        do {
            try {
                $this->pdo = new PDO($this->getDsn(), $this->username, $this->password, $this->options);
            } catch (Exception $exception) {
                if ($this->retries) {
                    $this->retries -= 1;
                    sleep($this->retrySleep);

                    continue;
                }

                throw $exception;
            }
        } while (is_null($this->pdo));

        if (is_callable($this->onSuccessConnect)) {
            call_user_func($this->onSuccessConnect);
        }

        return $this->pdo;
    }

    /**
     * Encloses the keyword by enclosure char to escapes it.
     */
    public function enclose(string $keyword): string
    {
        if ($keyword === '*' || substr($keyword, 0, 1) == $this->encloseCharOpn) {
            return $keyword;
        }

        return $this->encloseCharOpn . $keyword . $this->encloseCharCls;
    }

    /**
     * Converts SELECT to COUNT rows format.
     */
    public function foundRowsSelect(string $select): string
    {
        $reg = '/(SELECT )(.*)( FROM .*)( ORDER BY .+)?( GROUP BY .+( HAVING .*)?)( LIMIT [\d]+)( OFFSET [\d]+.*)?/mi';
        $subst = '$1COUNT(0) AS found_rows$3;';

        return preg_replace($reg, $subst, $select);
    }

    /**
     * Gets the database name.
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Gets the DSN string.
     *
     * Must be implemented into child connector class.
     */
    public function getDsn(): string
    {
        return '';
    }

    /**
     * Returns the database user password.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Returns the PDO object.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Returns the database username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Converts SELECT command to its optimized form when limiting rows.
     */
    public function paginatedSelect(string $select): string
    {
        $reg = '/^(SELECT )(.*)$/mi';
        $subst = '$1$2';

        return preg_replace($reg, $subst, $select);
    }

    /**
     * Sets the database name.
     */
    public function setDatabase(string $name): void
    {
        if (!$name) {
            throw new SpringyException('Database name undefined.');
        }

        $this->database = $name;
    }

    /**
     * Sets the password to connect to the database.
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * Sets the username to connecto to.
     */
    public function setUsername(string $name): void
    {
        $this->username = $name;
    }
}
