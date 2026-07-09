<?php

/**
 * Relational database access class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy;

use Exception;
use PDO;
use PDOException;
use Springy\Core\Debug;
use Springy\Exceptions\SpringyException;

class DB
{
    // connection instances
    private static array $conectionIds = [];

    /// SQL Resource
    private $resSQL = null;
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
    // Datanase connection resource
    private PDO|null $dataConnect;
    /// Flag de habilitação do relatório de erros
    private $reportError = true;
    /// Flag do modo debug
    private static $dbDebug = false;
    /// Controle de falhas de conexão
    private static $conErrors = [];

    /**
     * Constructor.
     *
     * @param string   $dbIdentifier database configuration identifier.
     * @param int|null $cacheExpires cached query expiration time in seconds.
     */
    public function __construct(
        private string $dbIdentifier = 'default',
        private ?int $cacheExpires = null
    ) {
        $this->connect($this->dbIdentifier);
    }

    /**
     * Destruction method.
     */
    public function __destruct()
    {
        if (!is_null($this->resSQL)) {
            $this->resSQL->closeCursor();
            $this->resSQL = null;
        }
    }

    /**
     * Checks if there is a connection.
     *
     * @throws Exception
     */
    private function checkConnection(): void
    {
        if (is_null($this->dataConnect)) {
            trigger_error('No connection to database.', E_USER_ERROR);
        }
    }

    /**
     * Connects to the DBMS.
     *
     * @param string $identifier DB configuration key.
     */
    public function connect(string $identifier): void
    {
        $this->dataConnect = null;
        $this->dbIdentifier = $identifier;

        if (isset(self::$conErrors[$identifier])) {
            return;
        } elseif ((self::$conectionIds[$identifier] ?? null) instanceof PDO) {
            $this->dataConnect = self::$conectionIds[$identifier];

            return;
        }

        $conf = Configuration::get('db', $identifier);

        if (!$conf['host_name'] || !$conf['database']) {
            $this->reportError('Hostname or database not defined.');
        }

        // PDO configuration
        $pdoConf = [
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            // PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => $conf['persistent'] ?? true,
        ];

        if ($conf['database_type'] === 'mysql') {
            $pdoConf[
                defined('Pdo\\Mysql::ATTR_INIT_COMMAND')
                ? Pdo\Mysql::ATTR_INIT_COMMAND
                : PDO::MYSQL_ATTR_INIT_COMMAND
            ] = 'SET NAMES \'' . ($conf['charset'] ?? 'UTF8') . '\'';
        }

        /*
         * A variável abaixo é setada pois caso a conexão com o banco falhe, o
         * callback de erro será chamado e a variável já estará setada.
         * Caso a conexão seja feita com sucesso, a variavel é removida.
         */
        self::$conErrors[$identifier] = true;

        $retries = $conf['retries'] ?? 3;
        $sleep = $conf['sleep'] ?? 1;

        do {
            try {
                // a instância de conexão é estática, para nao criar uma nova a cada nova instãncia da classe
                self::$conectionIds[$identifier] = new PDO(
                    $conf['database_type'] . ':host=' . $conf['host_name'] . ';dbname=' . $conf['database'],
                    $conf['user_name'],
                    $conf['password'],
                    $pdoConf
                );
                unset(self::$conErrors[$identifier]);
            } catch (PDOException $error) {
                if ($retries) {
                    $retries -= 1;
                    sleep($sleep);

                    continue;
                }

                $callers = debug_backtrace();
                if (
                    !isset($callers[1])
                    || $callers[1]['class'] != Errors::class
                    || $callers[1]['function'] != 'sendReport'
                ) {
                    (new Errors())->process($error);
                }
            }
        } while (!isset(self::$conectionIds[$identifier]));

        unset($pdoConf);

        $this->dataConnect = self::$conectionIds[$identifier];
    }

    /**
     * Returns connection status.
     *
     * @param string $database DB configuration key.
     *
     * @deprecated 4.7.0
     */
    public static function connected(string $database = 'default'): bool
    {
        trigger_error(
            'DB::connected is deprecated since version 4.7.0 and will be removed in the next release.'
            . ' Use DB::isConnected instead.',
            E_USER_DEPRECATED
        );

        return !isset(self::$conErrors[$database])
            && (self::$conectionIds[$database] ?? null) instanceof PDO;
    }

    /**
     * Returns the status error repor.
     *
     * @param bool $status if defined set the report of errors on (true) or off (false).
     *
     * @return bool
     */
    public function errorReportStatus(?bool $status = null): bool
    {
        if (is_bool($status)) {
            $this->reportError = $status;
        }

        return $this->reportError;
    }

    /**
     * Sends the error occurrency to the webmaster.
     */
    private function reportError(string $msg): void
    {
        if (!$this->reportError) {
            return;
        }

        $errorInfo = [0, 0, 'Unknown error'];

        if ($this->resSQL) {
            $errorInfo = $this->resSQL->errorInfo();
        } elseif ($this->dataConnect) {
            $errorInfo = $this->dataConnect->errorInfo();
        }

        $dbt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $file = $dbt[0]['file'];
        $line = $dbt[0]['line'];

        // Send the report of error and kill the application
        (new Errors())->sendReport(
            hash('crc32', $msg . $errorInfo[1] . $this->lastQuery), // error id
            $msg . ' (' . $errorInfo[1] . ') ' . $errorInfo[2]
            . ' - ' . $this->lastQuery . ' Values: '
            . Debug::printRc($this->lastValues),
            500,
            new SpringyException($msg, E_USER_ERROR, null, $file, $line)
        );
    }

    /**
     * Sets the database debug state.
     */
    public static function debug(bool $debug): void
    {
        self::$dbDebug = $debug;
    }

    /**
     * Begins a DB transaction.
     */
    public function beginTransaction(): void
    {
        $this->checkConnection();
        $this->dataConnect->beginTransaction();
    }

    /**
     * Rolls back a DB transaction.
     */
    public function rollBack(): void
    {
        $this->checkConnection();
        $this->dataConnect->inTransaction() && $this->dataConnect->rollBack();
    }

    /**
     * Commits a DB transaction.
     */
    public function commit(): void
    {
        $this->checkConnection();
        $this->dataConnect->inTransaction() && $this->dataConnect->commit();
    }

    /**
     * Rolls back all active transactions.
     */
    public static function rollBackAll(): void
    {
        foreach (self::$conectionIds as $database) {
            $database->inTransaction() && $database->rollBack();
        }
    }

    /**
     * Returns current connection status.
     */
    public function isConnected(): bool
    {
        return $this->dataConnect instanceof PDO;
    }

    /**
     * Executes a query.
     *
     * @param string   $sql
     * @param array    $prepareParams
     * @param int|null $cacheLifeTime cache expiration time (in seconds) for SELECT queries or null for no cached query.
     *
     * @return bool
     */
    public function execute($sql, array $prepareParams = [], $cacheLifeTime = null)
    {
        $this->sqlErrorCode = null;
        $this->sqlErrorInfo = null;
        self::$sqlNum++;

        /*
         * Verifica se está sendo usado o recurso de contagem de linhas
         * encontrados da última consulta do MySQL e cria um comando único
         */
        if (
            (is_int($this->cacheExpires) || is_int($cacheLifeTime))
            && mb_strtoupper(substr(ltrim($sql), 0, 19)) == 'SELECT FOUND_ROWS()'
            && mb_strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT '
        ) {
            $this->lastQuery = $sql . '; /* ' . md5(
                implode('//', array_merge([$this->lastQuery], $this->lastValues))
            ) . ' */';
        } else {
            $this->lastQuery = $sql;
        }

        $this->lastValues = $prepareParams;
        $sql = null;

        // Recupera a configuração de cache
        $dbcache = Configuration::get('db.cache');
        // Limpa o que estiver em memória e tiver sido carregado de cache
        $this->cacheStatement = null;
        // Configuração de cache está ligada?
        if (
            is_array($this->lastValues)
            && is_array($dbcache)
            && isset($dbcache['type'])
            && $dbcache['type'] == 'memcached'
        ) {
            $cacheKey = md5(implode('//', array_merge([$this->lastQuery], $this->lastValues)));
            $this->resSQL = null;
            // O comando é um SELECT e é para guardar em cache?
            if (
                (is_int($this->cacheExpires) || is_int($cacheLifeTime))
                && mb_strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT '
            ) {
                try {
                    $mc = new \Memcached();
                    $mc->addServer($dbcache['server_addr'], $dbcache['server_port']);
                    if ($sql = $mc->get('cacheDB_' . $cacheKey)) {
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
            $this->resSQL = $this->dataConnect->prepare($this->lastQuery);

            if ($this->resSQL === false) {
                $this->sqlErrorCode = $this->dataConnect->errorCode();
                $this->sqlErrorInfo = $this->dataConnect->errorInfo();
                $this->reportError('Error preparing query.');

                return false;
            }

            if (count($this->lastValues)) {
                $numeric = 0;

                foreach ($this->lastValues as $key => $where) {
                    $param = match (gettype($where)) {
                        'boolean' => PDO::PARAM_BOOL,
                        'integer' => PDO::PARAM_INT,
                        'NULL' => PDO::PARAM_NULL,
                        default => PDO::PARAM_STR,
                    };

                    $this->resSQL->bindValue(
                        is_numeric($key) ? (++$numeric) : (':' . $key),
                        $where,
                        $param
                    );
                }
            }

            $this->resSQL->closeCursor();
            if ($this->resSQL->execute() === false) {
                $this->sqlErrorCode = $this->resSQL->errorCode();
                $this->sqlErrorInfo = $this->resSQL->errorInfo();
                $this->reportError('Error executing query.');

                return false;
            }

            // Configuração de cache está ligada?
            if (is_array($dbcache) && isset($dbcache['type']) && $dbcache['type'] == 'memcached') {
                // O comando é um SELECT e é para guardar em cache?
                if (
                    (is_int($this->cacheExpires) || is_int($cacheLifeTime))
                    && mb_strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT '
                ) {
                    try {
                        $mc = new \Memcached();
                        $mc->addServer($dbcache['server_addr'], $dbcache['server_port']);
                        $this->cacheStatement = $this->fetchAll();
                        $mc->set(
                            'cacheDB_' . $cacheKey,
                            $this->cacheStatement,
                            min(
                                is_int($cacheLifeTime) ? $cacheLifeTime : 86400,
                                is_int($this->cacheExpires) ? $this->cacheExpires : 86400
                            )
                        );
                        unset($mc);
                        $this->resSQL->closeCursor();
                        $this->resSQL = null;
                    } catch (Exception $e) {
                        debug($this->lastQuery, 'Erro: ' . $e->getMessage());
                    }
                }
            }
        }

        if (self::$dbDebug || Configuration::get('system.sql_debug')) {
            $conf = Configuration::get('db', $this->dbIdentifier);

            debug(
                '<pre>' . $this->lastQuery . '</pre><br />Values: ' .
                Debug::printRc($this->lastValues) . '<br />' .
                'Affected Rows: ' . $this->affectedRows() . '<br />' .
                'DB: ' . ($conf['database'] ?? 'not set'),
                'SQL #' . self::$sqlNum,
                false
            );
        }

        return true;
    }

    /**
     * Returns the last executed query.
     */
    public function lastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * Returns the last error code occurred.
     */
    public function errorCode(): ?string
    {
        return $this->dataConnect->errorCode();
    }

    /**
     * Returns the last error information array.
     */
    public function errorInfo(): array
    {
        return $this->dataConnect->errorInfo();
    }

    /**
     * Returns the string with error code occurred on last execute method call.
     */
    public function statmentErrorCode(): ?string
    {
        return $this->sqlErrorCode;
    }

    /**
     * Returns the array with information about error occurred on last execute method call.
     */
    public function statmentErrorInfo(): array
    {
        return $this->sqlErrorInfo ?? [0, null, 'No error'];
    }

    /**
     * Returns the database driver name of the current connection.
     */
    public function driverName(): string
    {
        return $this->dataConnect instanceof PDO
            ? $this->dataConnect->getAttribute(PDO::ATTR_DRIVER_NAME)
            : '';
    }

    /**
     * Returns the DBMS version informations.
     */
    public function serverVersion(): mixed
    {
        return $this->dataConnect instanceof PDO
            ? $this->dataConnect->getAttribute(PDO::ATTR_SERVER_VERSION)
            : '';
    }

    /**
     * Returns the value of the auto increment columns in last INSERT.
     *
     * @param string|null $indice
     *
     * @return int
     */
    public function lastInsertedId($indice = null)
    {
        return $this->dataConnect->lastInsertId($indice);
    }

    /**
     * Returns the amount of affected rows of the last query.
     *
     * @return int
     */
    public function affectedRows()
    {
        return is_null($this->cacheStatement)
            ? $this->resSQL->rowCount()
            : count($this->cacheStatement);
    }

    /**
     * Returns all rows of the resultset.
     *
     * @param int $resultType
     */
    public function fetchAll($resultType = PDO::FETCH_ASSOC): array
    {
        return $this->cacheStatement
            ?? ($this->resSQL ? $this->resSQL->fetchAll($resultType) : []);
    }

    /**
     * Returns the first row of the resultset.
     *
     * @param int $resultType
     */
    public function fetchFirst($resultType = PDO::FETCH_ASSOC): array|bool
    {
        return is_null($this->cacheStatement)
            ? ($this->resSQL ? $this->resSQL->fetch($resultType) : false)
            : reset($this->cacheStatement);
    }

    /**
     * Returns the previous row of the resultset.
     *
     * @param int $resultType
     */
    public function fetchPrev($resultType = PDO::FETCH_ASSOC): array|bool
    {
        return is_null($this->cacheStatement)
            ? ($this->resSQL ? $this->resSQL->fetch($resultType, PDO::FETCH_ORI_PRIOR) : false)
            : prev($this->cacheStatement);
    }

    /**
     * Returns the next row of the resultset.
     *
     * @param int $resultType
     */
    public function fetchNext($resultType = PDO::FETCH_ASSOC): array|bool
    {
        if (!is_null($this->cacheStatement)) {
            $current = current($this->cacheStatement);
            next($this->cacheStatement);

            return $current;
        }

        return $this->resSQL ? $this->resSQL->fetch($resultType) : false;
    }

    /**
     * Returns the last row of the resultset.
     *
     * @param int $resultType
     */
    public function fetchLast($resultType = PDO::FETCH_ASSOC): array|bool
    {
        return is_null($this->cacheStatement)
            ? ($this->resSQL ? $this->resSQL->fetch($resultType, PDO::FETCH_ORI_LAST) : false)
            : end($this->cacheStatement);
    }

    /**
     * Returns the value of a column.
     */
    public function getColumn(mixed $var = 0): mixed
    {
        if (!is_null($this->cacheStatement)) {
            return current($this->cacheStatement)[$var];
        } elseif ($this->resSQL && is_numeric($var)) {
            return $this->resSQL->fetchColumn($var);
        }

        $this->reportError($var . ' is not defined in select or data is empty.');

        return false;
    }

    /**
     * Converts a date string in ISO format to UNIX timestamp.
     *
     * @param string $dateTime
     *
     * @deprecated 4.7.0
     *
     * @return int
     */
    public static function makeDbDateTime($dateTime)
    {
        trigger_error(
            'DB::makeDbDateTime is deprecated since version 4.7.0 and will be removed in the next release.'
            . ' Use DateTime::createFromFormat instead.',
            E_USER_DEPRECATED
        );

        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/', $dateTime)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime)->getTimestamp();
        } elseif (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})/', $dateTime)) {
            return \DateTime::createFromFormat('d/m/Y H:i:s', $dateTime)->getTimestamp();
        }
    }
}
