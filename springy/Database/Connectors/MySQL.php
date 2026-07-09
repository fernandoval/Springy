<?php

/**
 * DBMS connector for MySQL servers.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Connectors;

use PDO;
use Springy\Exceptions\SpringyException;

class MySQL extends Connector implements ConnectorInterface
{
    protected string $charset;
    protected string $host;
    protected int $port;
    protected string $socket;

    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $this->charset = $settings['charset'] ?? 'utf8mb4';
        $this->host = $settings['host'] ?? '';
        $this->onSuccessConnect = $this->afterConnectSettings(...);
        $this->port = $settings['port'] ?? 3306;
        $this->socket = $settings['socket'] ?? '';
        $this->retries = $settings['retries'] ?? 3;
        $this->retrySleep = $settings['retry_sleep'] ?? 1;

        if (!$this->host && !$this->socket) {
            throw new SpringyException('Undefined database server host and socket.');
        }

        $this->options[
            defined('Pdo\\Mysql::ATTR_INIT_COMMAND')
                ? Pdo\Mysql::ATTR_INIT_COMMAND
                : PDO::MYSQL_ATTR_INIT_COMMAND
        ] = 'SET NAMES \'' . $this->charset . '\'';
        $this->options[PDO::ATTR_PERSISTENT] = $config['persistent'] ?? true;
    }

    /**
     * Configures database connection after the connection stablished.
     *
     * @return void
     */
    protected function afterConnectSettings(): void
    {
        if ($this->timezone) {
            $this->pdo->prepare('SET time_zone="' . $this->timezone . '"')->execute();
        }
    }

    /**
     * Gets string for HOST or SOCKET connector DSN.
     *
     * @return string
     */
    protected function getHostOrSocket(): string
    {
        return $this->socket
            ? 'unix_socket=' . $this->socket
            : 'host=' . $this->host . ';port=' . $this->port;
    }

    public function foundRowsSelect(string $select): string
    {
        $reg = '/^(SELECT )(.*)$/mi';
        $subst = '$1FOUND_ROWS() AS found_rows;';

        return preg_replace($reg, $subst, $select);
    }

    /**
     * Returns the name of function to get current date and time from DBMS.
     */
    public function getCurrDate(): string
    {
        return 'NOW()';
    }

    public function getDsn(): string
    {
        return 'mysql:' . $this->getHostOrSocket() . ';dbname=' . $this->database;
    }
}
