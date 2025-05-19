<?php

/**
 * Relational database access class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database;

use Exception;
use Memcached;
use PDO;
use PDOStatement;
use Springy\Database\Connectors\Connector;
use Springy\Exceptions\SpringyException;
use Throwable;

class Connection
{
    use LostConnectionDetector;

    /** @var array cache configuration */
    protected array $cache;
    /** @var int the cache life time for next SQL statement */
    protected int $cacheLifeTime;
    /** @var int the style to fetch rows statement */
    protected int $fetchStyle;
    /** @var string current identity connection */
    protected string $identity;
    /** @var string last query execution error */
    protected string $lastError;
    /** @var string last query executed */
    protected string $lastQuery;
    /** @var array last query execution prepare statements */
    protected array $lastValues;
    /** @var PDOStatement|array the SQL statement */
    protected PDOStatement|array|null $statement;

    /** @var array connection instances */
    protected static array $conectionIds = [];

    public function __construct(?string $identity = null)
    {
        $this->cacheLifeTime = 0;
        $this->fetchStyle = PDO::FETCH_ASSOC;
        $this->identity = $identity ?? config_get('database.default');
        $this->lastError = '';
        $this->lastQuery = '';
        $this->lastValues = [];
        $this->statement = null;

        $this->connect();
    }

    public function __destruct()
    {
        if ($this->statement instanceof PDOStatement) {
            $this->statement->closeCursor();
        }

        $this->statement = null;
    }

    /**
     * Bind values to parameters.
     */
    protected function bindParameters(): void
    {
        if (!count($this->lastValues)) {
            return;
        }

        $counter = 0;

        foreach ($this->lastValues as $key => $value) {
            $param = match (gettype($value)) {
                'boolean' => PDO::PARAM_BOOL,
                'integer' => PDO::PARAM_INT,
                'NULL' => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $this->bindValue($key, $value, $param, $counter);
        }
    }

    /**
     * Binds a value to a parameter.
     */
    protected function bindValue(mixed $key, mixed $value, int $param, int &$counter): void
    {
        if (is_numeric($key)) {
            $this->statement->bindValue(++$counter, $value, $param);

            return;
        }

        $this->statement->bindValue(':' . $key, $value, $param);
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     */
    protected function checkMissingConnection(): void
    {
        if (is_null($this->getPdo())) {
            $this->connect();
        }
    }

    /**
     * Executes the quary again if is caused by lost connection.
     *
     * @throws PDOException
     */
    protected function executeAgainIfLostConnection(Throwable $err): void
    {
        if (!$this->isLostConnection($err)) {
            throw $err;
        }

        try {
            $this->lastError = '';
            $this->executeQuery();
        } catch (Throwable $err) {
            debug($err->getMessage());

            throw $err;
        }
    }

    /**
     * Executes the query.
     *
     * @throws PDOException
     */
    protected function executeQuery(): void
    {
        try {
            $this->statement = $this->getPdo()->prepare(
                $this->lastQuery,
                [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
            );

            $this->bindParameters();
            $this->statement->closeCursor();
            $this->statement->execute();
        } catch (Throwable $err) {
            $this->lastError = $err->getMessage();

            throw $err;
        }

        if ($this->cacheLifeTime) {
            $this->saveCache();
        }
    }

    /**
     * Gets the PDO object from current connection identity.
     */
    protected function getPdo(): PDO
    {
        if (!isset(self::$conectionIds[$this->identity])) {
            $this->connect();
        }

        return self::$conectionIds[$this->identity]->getPdo();
    }

    /**
     * Loads rows from cache if applicable.
     */
    protected function loadCache(): void
    {
        // Clears the cache statement
        $this->statement = null;

        if ($this->cacheLifeTime <= 0 || $this->cache['driver'] != 'memcached') {
            return;
        }

        $cacheKey = md5(implode('//', array_merge([$this->lastQuery], $this->lastValues)));

        try {
            $mmc = new Memcached();
            $mmc->addServer($this->cache['host'], $this->cache['port']);
            $sql = $mmc->get('dbCache_' . $cacheKey);

            if ($sql) {
                $this->statement = $sql;
            }
        } catch (Exception $e) {
            $this->statement = null;
        }
    }

    /**
     * Saves the SQL statement rows in cache if applicable.
     */
    protected function saveCache(): void
    {
        if ($this->cacheLifeTime <= 0 || $this->cache['driver'] != 'memcached') {
            return;
        }

        $cacheKey = md5(implode('//', array_merge([$this->lastQuery], $this->lastValues)));

        try {
            $mmc = new Memcached();
            $mmc->addServer($this->cache['host'], $this->cache['port']);

            $rows = $this->getAll();

            $mmc->set('dbCache_' . $cacheKey, $rows, $this->cacheLifeTime);

            $this->statement->closeCursor();
            $this->statement = $rows;
        } catch (Throwable $err) {
            debug($this->lastQuery);
            debug('Erro: ' . $err->getMessage());
        }
    }

    /**
     * Saves the query string in lastQuery property.
     */
    protected function setLastQuery(string $query): void
    {
        if (
            $this->cacheLifeTime > 0
            && strtoupper(substr(ltrim($query), 0, 19)) == 'SELECT FOUND_ROWS()'
            && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT '
        ) {
            $this->lastQuery = $query . '; /* ' . md5(
                implode(
                    '//',
                    array_merge([$this->lastQuery], $this->lastValues)
                )
            ) . ' */';

            return;
        }

        $this->lastQuery = $query;
    }

    /**
     * Connects to the DBMS.
     *
     * @throws SpringyException
     */
    public function connect(): void
    {
        // Is a connector to the identity?
        if (
            isset(self::$conectionIds[$this->identity])
            && self::$conectionIds[$this->identity]->getPdo() !== null
        ) {
            return;
        }

        $driver = config_get('database.connections.' . $this->identity . '.driver');

        if (!class_exists($driver)) {
            throw new SpringyException('Database driver not found.');
        }

        /** @var Connector */
        $connector = new $driver(config_get('database.connections.' . $this->identity));

        if (!($connector instanceof Connector)) {
            throw new SpringyException('Database driver not supported.');
        }

        $this->cache = config_get(
            'database.cache',
            [
                'driver' => 'none',
            ]
        );

        self::$conectionIds[$this->identity] = $connector;
        $connector->connect();
    }

    /**
     * Closes database connection.
     */
    public function disconnect(): void
    {
        if (!isset(self::$conectionIds[$this->identity])) {
            return;
        }

        unset(self::$conectionIds[$this->identity]);
    }

    /**
     * Encloses the keyword by enclosure char to escapes it.
     */
    public function enclose(string $keyword): string
    {
        return $this->getConnector()->enclose($keyword);
    }

    /**
     * Returns true if connection was stablished.
     */
    public function isConnected(): bool
    {
        $connection = self::$conectionIds[$this->identity] ?? null;

        return ($connection !== null) && ($connection->getPdo() instanceof PDO);
    }

    /**
     * Begins a DB transaction.
     */
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }

    /**
     * Commits a DB transaction.
     */
    public function commit(): void
    {
        $this->getPdo()->commit();
    }

    /**
     * Executes a query and returns the quantity of rows affected.
     */
    public function execute(string $query, array $params = []): int
    {
        $this->run($query, $params);

        return $this->affectedRows();
    }

    /**
     * Rolls back a DB transaction.
     */
    public function rollBack(): void
    {
        $this->getPdo()->rollBack();
    }

    /**
     * Executes a query.
     */
    public function run(string $query, array $params = []): void
    {
        $this->lastError = '';
        $this->statement = null;

        $this->setLastQuery($query);

        $this->lastValues = $params;

        $this->loadCache();

        if ($this->statement !== null) {
            return;
        }

        $this->checkMissingConnection();

        try {
            $this->executeQuery();
        } catch (Throwable $err) {
            $this->executeAgainIfLostConnection($err);
        }
    }

    /**
     * Runs a select query and returns the array of found rows.
     */
    public function select(
        string $query,
        array $params = [],
        ?int $fetchStyle = null,
        int $cacheLifeTime = 0
    ): array {
        $this->cacheLifeTime = $cacheLifeTime;
        $this->run($query, $params);
        $this->fetchStyle = $fetchStyle ?? $this->fetchStyle;
        $this->statement = $this->getAll();
        $this->cacheLifeTime = 0;

        return $this->statement ?? [];
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     */
    public function affectedRows(): int
    {
        if ($this->statement instanceof PDOStatement) {
            return $this->statement->rowCount();
        } elseif (is_array($this->statement)) {
            return count($this->statement);
        }

        return 0;
    }

    /**
     * Returns the current row of the statement and moves the cursor to the next row.
     *
     * @return array|bool
     */
    public function fetch(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        $current = current($this->statement);
        next($this->statement);

        return $current;
    }

    /**
     * Returns all rows of the resultset.
     */
    public function getAll(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $rows = $this->statement->fetchAll($this->fetchStyle);
            $this->statement->closeCursor();
            $this->statement = $rows;
        }

        return $this->statement;
    }

    /**
     * Returns the value of a column.
     */
    public function getColumn(string $var): mixed
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        $current = current($this->statement);

        return $current[$var] ?? null;
    }

    /**
     * Gets the connector object.
     */
    public function getConnector(): Connector
    {
        if (!isset(self::$conectionIds[$this->identity])) {
            $this->connect();
        }

        return self::$conectionIds[$this->identity];
    }

    /**
     * Returns the current row of the resultset and moves the cursor to next record.
     */
    public function getCurrent(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        return current($this->statement);
    }

    /**
     * Returns the database driver name of the current connection.
     */
    public function getDriverName(): ?string
    {
        return $this->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Returns the last error occurred.
     */
    public function getError(): string
    {
        return $this->lastError;
    }

    /**
     * Resets the cursor to the first row of the statement and returns it.
     */
    public function getFirst(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        return reset($this->statement);
    }

    /**
     * Moves the cursor to the last row of the statement and returns it.
     */
    public function getLast(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        return end($this->statement);
    }

    /**
     * Returns the value of the auto increment columns in last INSERT.
     */
    public function getLastInsertedId(?string $name = null): int
    {
        return $this->getPdo()->lastInsertId($name);
    }

    /**
     * Returns the last executed query.
     */
    public function getLastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * Returns the next row of the statement.
     *
     * Be careful when using this method because it moves the cursos before fetch the record.
     */
    public function getNext(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        return next($this->statement);
    }

    /**
     * Moves the cursor the previous row of the statement and returns it.
     */
    public function getPrev(): array|bool
    {
        if ($this->statement instanceof PDOStatement) {
            $this->getAll();
        }

        return prev($this->statement);
    }

    /**
     * Returns the DBMS version informations.
     */
    public function getServerVersion(): mixed
    {
        return $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }
}
