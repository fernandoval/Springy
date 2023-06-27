<?php

/**
 * Relational database access class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.9.3
 */

namespace Springy;

use Exception;
use Springy\Core\Debug;
use Springy\Exceptions\SpringyException;

/**
 * Relational database access class.
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
     * Constructor.
     *
     * @param string   $database     DB configuration key.
     * @param int|null $cacheExpires cached query expiration time in seconds.
     */
    public function __construct($database = 'default', $cacheExpires = null)
    {
        $this->cacheExpires = $cacheExpires;
        $this->database = $database;
        $this->dataConnect = $this->connect($this->database);
    }

    /**
     * Destruction method.
     */
    public function __destruct()
    {
        if ($this->resSQL === null) {
            return;
        }

        $this->resSQL->closeCursor();
        $this->resSQL = null;
    }

    /**
     * Connects to the DBMS.
     *
     * @param string $database DB configuration key.
     *
     * @return PDO|bool
     */
    public function connect($database)
    {
        if (isset(self::$conErrors[$database])) {
            return false;
        }

        // Verifica se a instância já está definida e conectada
        if (isset(self::$conectionIds[$database])) {
            return self::$conectionIds[$database]['PDO'];
        }

        // Lê as configurações de acesso ao banco de dados
        $conf = Configuration::get('db', $database);

        // Verifica se o servidor é um pool (round robin)
        if ($conf['database_type'] == 'pool' && is_array($conf['host_name'])) {
            return $this->roundRobinConnect($database, $conf);
        }

        $pdoConf = [];
        if ($conf['database_type'] == 'mysql') {
            $pdoConf[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES \'' . ($conf['charset'] ?? 'UTF8') . '\'';
        }

        if ($conf['persistent']) {
            $pdoConf[\PDO::ATTR_PERSISTENT] = true;
        }

        /*
         * A variável abaixo é setada pois caso a conexão com o banco falhe, o callback de erro será chamado e a variável já estará setada.
         * Caso a conexão seja feita com sucesso, a variavel é removida.
         */
        self::$conErrors[$database] = true;

        if (!$conf['host_name'] || !$conf['database']) {
            $this->reportError('Hostname or database not defined.');
        }

        $retries = $conf['retries'] ?? 3;
        $sleep = $conf['sleep'] ?? 1;
        do {
            try {
                // a instância de conexão é estática, para nao criar uma nova a cada nova instãncia da classe
                self::$conectionIds[$database] = [
                    'PDO' => new \PDO(
                        $conf['database_type'] . ':host=' . $conf['host_name'] . ';dbname=' . $conf['database'],
                        $conf['user_name'],
                        $conf['password'],
                        $pdoConf
                    ),
                    'dbName' => $database,
                ];
                unset(self::$conErrors[$database]);
            } catch (\PDOException $error) {
                if ($retries) {
                    $retries -= 1;
                    sleep($sleep);

                    continue;
                }

                $callers = debug_backtrace();
                if (
                    !isset($callers[1])
                    || $callers[1]['class'] != 'Springy\Errors'
                    || $callers[1]['function'] != 'sendReport'
                ) {
                    (new Errors())->process($error);
                }
            }
        } while (!isset(self::$conectionIds[$database]));

        unset($pdoConf);

        return self::$conectionIds[$database]['PDO'];
    }

    /**
     * Round robin database connection.
     *
     * @param string $database DB configuration key.
     * @param array  $dbconf   Database configutation.
     *
     * @return PDO|bool
     */
    private function roundRobinConnect($database, $dbconf)
    {
        // Lê as configurações de controle de round robin
        $roundRobin = Configuration::get('db', 'round_robin');

        // Efetua controle de round robin por Memcached
        if ($roundRobin['type'] == 'memcached') {
            $memCached = new \Memcached();
            $memCached->addServer($roundRobin['server_addr'], $roundRobin['server_port']);

            // Define o próximo servidor do pool
            if (!($actual = (int) $memCached->get('dbrr_' . $database))) {
                $actual = 0;
            }
            if (++$actual >= count($dbconf['host_name'])) {
                $actual = 0;
            }

            $memCached->set('dbrr_' . $database, $actual, 0);
        }
        // Efetua controle de round robin em arquivo
        elseif ($roundRobin['type'] == 'file') {
            // Define o próximo servidor do pool
            if ((!file_exists($roundRobin['server_addr'] . DIRECTORY_SEPARATOR . 'dbrr_' . $database)) || !($actual = (int) file_get_contents($roundRobin['server_addr'] . DIRECTORY_SEPARATOR . 'dbrr_' . $database))) {
                $actual = 0;
            }
            if (++$actual >= count($dbconf['host_name'])) {
                $actual = 0;
            }

            file_put_contents($roundRobin['server_addr'] . DIRECTORY_SEPARATOR . 'dbrr_' . $database, $actual);
        } else {
            return false;
        }

        // Tenta conectar ao banco e retorna o resultado
        self::$conectionIds[$database] = [
            'PDO'    => $this->connect($dbconf['host_name'][$actual]),
            'dbName' => $dbconf['host_name'][$actual],
        ];

        return self::$conectionIds[$database]['PDO'];
    }

    /**
     * Returns connection status.
     *
     * @param string $database DB configuration key.
     *
     * @return bool
     */
    public static function connected($database = 'default')
    {
        return !isset(self::$conErrors[$database]) && isset(self::$conectionIds[$database]) && self::$conectionIds[$database]['PDO'];
    }

    /**
     * Closes database connection.
     *
     * @return void
     */
    public function disconnect()
    {
        if (static::connected($this->database)) {
            ///	a instância de conexão é estática, para não criar uma nova a cada nova instância da classe
            unset(self::$conectionIds[$this->database]['PDO']);
            $this->dataConnect = false;
        }

        unset(self::$conectionIds[$this->database]);
    }

    /**
     * Returns the status error repor.
     *
     * @param bool $status if defined set the report of errors on (true) or off (false).
     *
     * @return bool
     */
    public function errorReportStatus($status = null)
    {
        if (is_bool($status)) {
            $this->reportError = $status;
        }

        return $this->reportError;
    }

    /**
     * Sends the error occurrency to the webmaster.
     *
     * @param string $msg
     *
     * @return void
     */
    private function reportError($msg)
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
            . Debug::print_rc($this->lastValues),
            500,
            new SpringyException($msg, E_USER_ERROR, null, $file, $line)
        );
    }

    /**
     * Sets the database debug state.
     *
     * @param bool $debug
     *
     * @return void
     */
    public static function debug($debug)
    {
        self::$dbDebug = $debug;
    }

    /**
     * Begins a DB transaction.
     *
     * @param string $database DB configuration key.
     *
     * @return void
     */
    public static function beginTransaction($database = 'default')
    {
        self::connect($database)->beginTransaction();
    }

    /**
     * Rolls back a DB transaction.
     *
     * @param string $database DB configuration key.
     *
     * @return void
     */
    public static function rollBack($database = 'default')
    {
        self::connect($database)->rollBack();
    }

    /**
     * Commits a DB transaction.
     *
     * @param string $database DB configuration key.
     *
     * @return void
     */
    public static function commit($database = 'default')
    {
        self::connect($database)->commit();
    }

    /**
     * Rolls back all active transactions.
     *
     * @return void
     */
    public static function rollBackAll()
    {
        foreach (self::$conectionIds as $database) {
            if ($database['PDO']->inTransaction()) {
                $database['PDO']->rollBack();
            }
        }
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

        // Verifica se está sendo usado o recurso de contagem de linhas encontrados da última consulta do MySQL e cria um comando único
        if ((is_int($this->cacheExpires) || is_int($cacheLifeTime))
            && strtoupper(substr(ltrim($sql), 0, 19)) == 'SELECT FOUND_ROWS()'
            && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT ') {
            $this->lastQuery = $sql . '; /* ' . md5(implode('//', array_merge([$this->lastQuery], $this->lastValues))) . ' */';
        } else {
            $this->lastQuery = $sql;
        }

        $this->lastValues = $prepareParams;
        $sql = null;

        // Recupera a configuração de cache
        $dbcache = Configuration::get('db', 'cache');
        // Limpa o que estiver em memória e tiver sido carregado de cache
        $this->cacheStatement = null;
        // Configuração de cache está ligada?
        if (is_array($this->lastValues) && is_array($dbcache) && isset($dbcache['type']) && $dbcache['type'] == 'memcached') {
            $cacheKey = md5(implode('//', array_merge([$this->lastQuery], $this->lastValues)));
            $this->resSQL = null;
            // O comando é um SELECT e é para guardar em cache?
            if ((is_int($this->cacheExpires) || is_int($cacheLifeTime)) && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT ') {
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
            if (($this->resSQL = $this->dataConnect->prepare($this->lastQuery)) === false) {
                $this->sqlErrorCode = $this->resSQL->errorCode();
                $this->sqlErrorInfo = $this->resSQL->errorInfo();
                $this->reportError('Error preparing query.');

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
                        $this->resSQL->bindValue(':' . $key, $where, $param);
                    }
                }
                unset($key, $where, $param, $numeric);
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
                if ((is_int($this->cacheExpires) || is_int($cacheLifeTime)) && strtoupper(substr(ltrim($this->lastQuery), 0, 7)) == 'SELECT ') {
                    try {
                        $mc = new \Memcached();
                        $mc->addServer($dbcache['server_addr'], $dbcache['server_port']);
                        $this->cacheStatement = $this->fetchAll();
                        $mc->set('cacheDB_' . $cacheKey, $this->cacheStatement, min(is_int($cacheLifeTime) ? $cacheLifeTime : 86400, is_int($this->cacheExpires) ? $this->cacheExpires : 86400));
                        unset($mc);
                        $this->resSQL->closeCursor();
                        $this->resSQL = null;
                    } catch (Exception $e) {
                        debug($this->lastQuery, 'Erro: ' . $e->getMessage());
                    }
                }
            }
        }

        if (self::$dbDebug || Configuration::get('system', 'sql_debug')) {
            $conf = Configuration::get('db', self::$conectionIds[$this->database]['dbName']);

            debug(
                '<pre>' . $this->lastQuery . '</pre><br />Values: ' .
                Debug::print_rc($this->lastValues) . '<br />' .
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
     *
     * @return string
     */
    public function lastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * Returns the last error code occurred.
     *
     * @return string
     */
    public function errorCode()
    {
        return $this->dataConnect->errorCode();
    }

    /**
     * Returns the last error information array.
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->dataConnect->errorInfo();
    }

    /**
     * Returns the string with error code occurred on last execute method call.
     *
     * @return string
     */
    public function statmentErrorCode()
    {
        return $this->sqlErrorCode;
    }

    /**
     * Returns the array with information about error occurred on last execute method call.
     *
     * @return array
     */
    public function statmentErrorInfo()
    {
        return $this->sqlErrorInfo;
    }

    /**
     * Returns the database driver name of the current connection.
     *
     * @return string
     */
    public function driverName()
    {
        if ($this->dataConnect === false) {
            return '';
        }

        return $this->dataConnect->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Returns the DBMS version informations.
     *
     * @return int
     */
    public function serverVersion()
    {
        return $this->dataConnect->getAttribute(\PDO::ATTR_SERVER_VERSION);
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
        if ($this->cacheStatement === null) {
            return $this->resSQL->rowCount();
        }

        return count($this->cacheStatement);
    }

    /**
     * Returns all rows of the resultset.
     *
     * @param int $resultType
     *
     * @return array|bool
     */
    public function fetchAll($resultType = \PDO::FETCH_ASSOC)
    {
        if ($this->cacheStatement !== null) {
            return $this->cacheStatement;
        } elseif ($this->resSQL) {
            return $this->resSQL->fetchAll($resultType);
        }

        return false;
    }

    /**
     * Returns the first row of the resultset.
     *
     * @param int $resultType
     *
     * @return array|bool
     */
    public function fetchFirst($resultType = \PDO::FETCH_ASSOC)
    {
        if ($this->cacheStatement !== null) {
            return reset($this->cacheStatement);
        } elseif ($this->resSQL) {
            return $this->resSQL->fetch($resultType, \PDO::FETCH_ORI_FIRST);
        }

        return false;
    }

    /**
     * Returns the previous row of the resultset.
     *
     * @param int $resultType
     *
     * @return array|bool
     */
    public function fetchPrev($resultType = \PDO::FETCH_ASSOC)
    {
        if ($this->cacheStatement !== null) {
            return prev($this->cacheStatement);
        } elseif ($this->resSQL) {
            return $this->resSQL->fetch($resultType, \PDO::FETCH_ORI_PRIOR);
        }

        return false;
    }

    /**
     * Returns the next row of the resultset.
     *
     * @param int $resultType
     *
     * @return array|bool
     */
    public function fetchNext($resultType = \PDO::FETCH_ASSOC)
    {
        if ($this->cacheStatement !== null) {
            $current = current($this->cacheStatement);
            next($this->cacheStatement);

            return $current;
        } elseif ($this->resSQL) {
            return $this->resSQL->fetch($resultType);
        }

        return false;
    }

    /**
     * Returns the last row of the resultset.
     *
     * @param int $resultType
     *
     * @return array|bool
     */
    public function fetchLast($resultType = \PDO::FETCH_ASSOC)
    {
        if ($this->cacheStatement !== null) {
            return end($this->cacheStatement);
        } elseif ($this->resSQL) {
            return $this->resSQL->fetch($resultType, \PDO::FETCH_ORI_LAST);
        }

        return false;
    }

    /**
     * Returns the value of a column.
     *
     * @param int $var
     *
     * @return mixed
     */
    public function getColumn($var = 0)
    {
        if ($this->cacheStatement !== null) {
            $current = current($this->cacheStatement);

            return $current[$var];
        } elseif ($this->resSQL && is_numeric($var)) {
            return $this->resSQL->fetchColumn($var);
        }

        $this->reportError($var . ' is not defined in select or data is empty.');

        return false;
    }

    /**
     * Converts a brazilian date string to ISO DBMS format.
     *
     * @param string $datetime Date and time in brazilian format (d/m/Y H:i:s)
     * @param bool   $flgtime
     *
     * @return string
     */
    public static function castDateBrToDb($datetime, $flgtime = false)
    {
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', $datetime);

        return $flgtime ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
    }

    /**
     * Converts an ISO DB date string to brazilian format.
     *
     * @param string $datetime
     * @param bool   $flgtime
     * @param bool   $sec
     *
     * @return string
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
     * Converts a date string in ISO format to UNIX timestamp.
     *
     * @param string $dateTime
     *
     * @return int
     */
    public static function makeDbDateTime($dateTime)
    {
        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/', $dateTime)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime)->getTimestamp();
        } elseif (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})/', $dateTime)) {
            return \DateTime::createFromFormat('d/m/Y H:i:s', $dateTime)->getTimestamp();
        }
    }

    /**
     * Converts a date string in DBMS ISO format to a long date string in brazilian portuguese.
     *
     * @param string $dataTimeStamp
     *
     * @return string
     */
    public static function longBrazilianDate($dataTimeStamp)
    {
        $dateTime = static::makeDbDateTime($dataTimeStamp);
        $mes = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $numMes = (int) date('m', $dateTime);

        return date('d', $dateTime) . ' de ' . $mes[--$numMes] . ' de ' . date('Y', $dateTime);
    }
}
