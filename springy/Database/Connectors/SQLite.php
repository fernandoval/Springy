<?php

/**
 * DBMS connector for SQLite databases.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Connectors;

use Springy\Exceptions\SpringyException;

class SQLite extends Connector implements ConnectorInterface
{
    protected $encloseCharOpn = '"';
    protected $encloseCharCls = '"';

    /**
     * Returns the name of function to get current date and time from DBMS.
     */
    public function getCurrDate(): string
    {
        return 'datetime(\'now\')';
    }

    public function getDsn(): string
    {
        return 'sqlite:' . $this->database;
    }

    public function setDatabase(string $database): void
    {
        parent::setDatabase($database);

        if ($database !== ':memory:') {
            $path = realpath($database);

            if ($path === false) {
                throw new SpringyException('Database "' . $database . '" does not exists.');
            }
        }
    }
}
