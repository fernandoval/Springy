<?php

/**
 * Class to quote keywords in database commands.
 *
 * WARNING! WARNING! THIS CLASS IS EXPERIMENTAL. DO NOT USE IT!
 *
 * @copyright 2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.3.0
 */

namespace Springy\DB;

/**
 * Class to quote keywords in database commands.
 */
class Utils
{
    /// Character that opens a quoted keyword
    protected $openQuote = '';
    /// Character that closes a quoted keyword
    protected $closeQuote = '';

    /**
     * Constructor.
     *
     * @param string $driverName the database driver name.
     */
    public function __construct($driverName)
    {
        switch ($driverName) {
            case 'db2':
            case 'ibm':
            case 'ibm-db2':
            case 'mysql':
                $this->openQuote = $this->closeQuote = '`';

                return;
            case 'firebird':
            case 'informix':
            case 'oci':
            case 'oracle':
            case 'pgsql':
            case 'sqlite':
                $this->openQuote = $this->closeQuote = '"';

                return;
            case 'mssql':
            case 'sqlsrv':
                $this->openQuote = '[';
                $this->closeQuote = ']';

                return;
        }
    }

    /**
     * Quotes the keyword.
     *
     * Some keywords such as SELECT, DELETE, or BIGINT, are reserved and require
     * special treatment for use as identifiers such as table and column names.
     *
     * @param string $keyword the named to be escaped.
     *
     * @return string
     */
    public function quote($keyword)
    {
        if (!$keyword || $keyword == '*' || substr($keyword, 0, 1) == $this->openQuote) {
            return $keyword;
        }

        return $this->openQuote . $keyword . $this->closeQuote;
    }

    /**
     * Parses the keywords in the expression.
     *
     * @param string $table      name of the table.
     * @param string $expression the expression with keyword(s).
     *
     * @deprecated 4.6
     *
     * @return string
     */
    public function parseKeywords($table, $expression)
    {
        $expression = trim($expression);
        if ($expression === '*') {
            return $this->quote($table) . '.*';
        } elseif (preg_match('/^[\w]+$/', $expression)) {
            return $this->quote($table) . '.' . $this->quote($expression);
        }

        return $expression;

        // O código abaixo tentar escapar os nomes de todas as colunas na expressão.
        // Mas há um bug quando há alguma palavra reservada dentro de uma função.
        //
        // Exemplo:
        // COUNT(DISTINCT stores.id) AS qtty
        //
        // O resultado dessa expressão causa erro de SQL por ficar da seguinte forma:
        // COUNT(`DISTINCTstores`.`id` AS `qtty`
        //
        // Estranhamente além de colocar o DISTINCT dentro do conteúdo escapado,
        // ainda remove o espaço entre ele e a coluna.

        // $alias = '';
        // if (preg_match_all('/^(.+)[\s ]+(as|AS)[\s ]+(.+)$/', $expression, $matches, PREG_SET_ORDER, 0)) {
        //     $expression = $matches[0][1];
        //     $alias = ' AS '.$this->quote($matches[0][3]);
        // }

        // $parsed = $this->_parseKeywords($table, $expression);

        // return $parsed.$alias;
    }
}
