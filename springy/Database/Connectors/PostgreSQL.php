<?php

/**
 * DBMS connector for PostgreSQL servers.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Connectors;

use PDO;
use Springy\Exceptions\SpringyException;

class PostgreSQL extends Connector implements ConnectorInterface
{
    protected $encloseCharOpn = '"';
    protected $encloseCharCls = '"';

    protected string $charset;
    protected string $host;
    protected int $port;
    protected string $schema;
    protected string $ssl; // the SSL options

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->charset = $config['charset'] ?? 'UTF8';
        $this->host = $settings['host'] ?? '';
        $this->onSuccessConnect = $this->afterConnectSettings(...);
        $this->port = $config['port'] ?? 5432;
        $this->retries = $config['retries'] ?? 3;
        $this->retrySleep = $config['retry_sleep'] ?? 1;
        $this->schema = $config['schema'] ?? '';

        if (!$this->host) {
            throw new SpringyException('Undefined database server host.');
        }

        unset($this->options[PDO::ATTR_EMULATE_PREPARES]);

        $this->ssl = '';

        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (isset($config[$option])) {
                $this->ssl .= ';' . $option . '=' . $config[$option];
            }
        }
    }

    /**
     * Configures database connection after the connection stablished.
     */
    protected function afterConnectSettings(): void
    {
        if ($this->charset) {
            $this->pdo->prepare('SET NAMES \'' . $this->charset . '\'')->execute();
        }

        if ($this->timezone) {
            $this->pdo->prepare('SET TIME ZONE \'' . $this->timezone . '\'')->execute();
        }

        if ($this->schema) {
            $this->pdo->prepare('SET SCHEMA \'' . $this->schema . '\'')->execute();
        }
    }

    public function foundRowsSelect(string $select): string
    {
        $reg = '/^(SELECT )(.*)$/mi';
        $subst = '';

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
        return 'pgsql:' . ($this->host ? 'host=' . $this->host : '')
            . ';port=' . $this->port
            . ';dbname=' . $this->database
            . $this->ssl;
    }

    public function paginatedSelect(string $select): string
    {
        $reg = '/^(SELECT )(.+)( FROM (.*)( LIMIT [\d]+)( OFFSET [\d]+)?.*){1}$/mi';
        $subst = '$1$2, COUNT(*) OVER() AS found_rows$3';

        return preg_replace($reg, $subst, $select);
    }
}
