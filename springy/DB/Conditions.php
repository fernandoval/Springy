<?php
/**
 * Class to construct database conditions clauses.
 *
 * @copyright 2016-2018 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.5.1.7
 */

namespace Springy\DB;

/**
 * Class to construct database conditions clauses.
 */
class Conditions
{
    /// Conditions array
    private $conditions = [];
    /// Parameters array
    private $parameters = [];

    /// Conditional constants
    const COND_AND = 'AND';
    const COND_OR = 'OR';

    /// Comparison constants
    const OP_EQUAL = '=';
    const OP_NOT_EQUAL = '!=';
    const OP_GREATER = '>';
    const OP_GREATER_EQUAL = '>=';
    const OP_LESS = '<';
    const OP_LESS_EQUAL = '<=';
    const OP_IN = 'IN';
    const OP_NOT_IN = 'NOT IN';
    const OP_IS = 'IS';
    const OP_IS_NOT = 'IS NOT';
    const OP_LIKE = 'LIKE';
    const OP_NOT_LIKE = 'NOT LIKE';
    const OP_MATCH = 'MATCH';
    const OP_MATCH_BOOLEAN_MODE = 'MATCH BOOLEAN';

    /// Comparison constants aliases
    const OP_EQUAL_ALIAS = 'EQ';
    const OP_NOT_EQUAL_ALIAS = 'NE';
    const OP_GREATER_ALIAS = 'GT';
    const OP_GREATER_EQUAL_ALIAS = 'GTE';
    const OP_LESS_ALIAS = 'LT';
    const OP_LESS_EQUAL_ALIAS = 'LTE';

    const EXPR_SIMPLE = 'exp';
    const EXPR_SUB = 'sub';

    /**
     * Constructor.
     *
     * @param array|null $conditions initial conditions.
     */
    public function __construct($conditions = null)
    {
        $this->conditions = [];

        if (is_array($conditions)) {
            $this->conditions = $conditions;
        }
    }

    /**
     * Converts an alias operator to the real operator.
     *
     * @param string $operator
     *
     * @return string
     */
    private function convertAliasOperator($operator)
    {
        switch ($operator) {
            case self::OP_EQUAL_ALIAS:
                return self::OP_EQUAL;
            case self::OP_NOT_EQUAL_ALIAS:
                return self::OP_NOT_EQUAL;
            case self::OP_GREATER_ALIAS:
                return self::OP_GREATER;
            case self::OP_GREATER_EQUAL_ALIAS:
                return self::OP_GREATER_EQUAL;
            case self::OP_LESS_ALIAS:
                return self::OP_LESS;
            case self::OP_LESS_EQUAL_ALIAS:
                return self::OP_LESS_EQUAL;
        }

        return $operator;
    }

    /**
     * Converts the condition array element to string form.
     *
     * Will feed the array of parameters too.
     *
     * @param array $condition    the condition.
     * @param bool  $omitCondType defines to omit the condition type at the beginning.
     *
     * @return string
     */
    private function condToString(array $condition, $omitCondType = false)
    {
        $expression = ($omitCondType ? '' : $condition['expression']);

        switch ($this->convertAliasOperator($condition['operator'])) {
            case self::OP_EQUAL:
            case self::OP_NOT_EQUAL:
            case self::OP_GREATER:
            case self::OP_GREATER_EQUAL:
            case self::OP_LESS:
            case self::OP_LESS_EQUAL:
            case self::OP_IS:
            case self::OP_IS_NOT:
            case self::OP_LIKE:
            case self::OP_NOT_LIKE:
                $this->parameters[] = $condition['value'];

                return $expression . ' ' . $condition['column'] . ' ' . $condition['operator'] . ' ?';
            case self::OP_IN:
            case self::OP_NOT_IN:
                $this->parameters = array_merge($this->parameters, $condition['value']);

                return $expression . ' ' . $condition['column'] . ($condition['operator'] === self::OP_NOT_IN ? ' NOT' : '') . ' IN (' . trim(str_repeat('?, ', count($condition['value'])), ', ') . ')';
            case self::OP_MATCH:
            case self::OP_MATCH_BOOLEAN_MODE:
                $this->parameters[] = $condition['value'];

                return $expression . ' MATCH (' . $condition['column'] . ') AGAINST (?' . ($condition['operator'] === self::OP_MATCH_BOOLEAN_MODE ? ' IN BOOLEAN MODE' : '') . ')';
        }

        throw new \Exception('Unknown condition operator.', 500);
    }

    /**
     * Converts the objet to a string in database conditions form.
     *
     * The values of the parameter will be in question mark form and can be obtained with params() method.
     *
     * @return string
     */
    public function __toString()
    {
        $this->parameters = [];
        $conditions = '';

        foreach ($this->conditions as $condition) {
            $condStr = '';

            if ($condition['type'] == self::EXPR_SIMPLE) {
                $condStr = trim($this->condToString($condition, empty($conditions)));
            } elseif ($condition['type'] == self::EXPR_SUB) {
                $sub = new self($condition['conditions']);
                $condStr = (empty($conditions) ? '' : $condition['expression'] . ' ') . '(' . $sub->parse() . ')';
                $this->parameters = array_merge($this->parameters, $sub->params());
                unset($sub);
            }

            $conditions .= ' ' . $condStr;
        }

        return trim($conditions);
    }

    /**
     * Clears the clause.
     *
     * @return void
     */
    public function clear()
    {
        $this->conditions = [];
        $this->parameters = [];
    }

    /**
     * Adds a condition to the clause.
     *
     * If $column is an array of conditions made by other conditions object,
     * this will create a set of conditions inside parentesis group.
     *
     * @param mixed  $column     the column name.
     * @param mixed  $value      the value of the condition.
     * @param string $operator   the comparison operator.
     * @param string $expression the expression to put before this condition.
     *
     * @return void
     */
    public function condition($column, $value = null, $operator = self::OP_EQUAL, $expression = self::COND_AND)
    {
        if (is_array($column) || is_object($column)) {
            $this->conditions[] = [
                'type'       => self::EXPR_SUB,
                'conditions' => is_object($column) ? $column->get() : $column,
                'expression' => ($value === null ? $expression : $value),
            ];

            return;
        }

        $this->conditions[] = [
            'type'       => self::EXPR_SIMPLE,
            'column'     => $column,
            'value'      => $value,
            'operator'   => strtoupper($operator),
            'expression' => $expression,
        ];
    }

    /**
     * Returns the number of conditions.
     *
     * @return int
     */
    public function count()
    {
        return count($this->conditions);
    }

    /**
     * Finds a column in the array of conditions.
     *
     * @param string $column     the name of the column.
     * @param array  $conditions the array of the conditions.
     *
     * @return mixed the condition for given column or false if not found.
     */
    private function _find($column, $conditions)
    {
        foreach ($conditions as $condition) {
            if ($condition['type'] === self::EXPR_SUB && $result = $this->_find($column, $condition['conditions'])) {
                return $result;
            } elseif ($condition['type'] === self::EXPR_SIMPLE && $condition['column'] == $column) {
                return $condition;
            }
        }

        return false;
    }

    /**
     * Gets the content of conditions in internal array form.
     *
     * @param mixed $column the name of the column or null to all conditions.
     *
     * @return mixed An array of conditions of false.
     */
    public function get($column = null)
    {
        if (is_null($column)) {
            return $this->conditions;
        }

        return $this->_find($column, $this->conditions);
    }

    /**
     * Gets the params after parse the clause.
     *
     * @return array of parameters in same sequence of the question marks into conditional string.
     */
    public function params()
    {
        return $this->parameters;
    }

    /**
     * An alias to __toString() method.
     *
     * @return string
     */
    public function parse()
    {
        return $this->__toString();
    }

    /**
     * Removes a conditions.
     *
     * Need revisions to check in sub expressions.
     *
     * @param string $column the name of the column to be removed from conditions.
     *
     * @return void
     */
    public function remove($column)
    {
        foreach ($this->conditions as $key => $condition) {
            if ($condition['type'] === self::EXPR_SIMPLE && $condition['column'] == $column) {
                unset($this->conditions[$key]);

                return;
            }
        }
    }

    /**
     * Populates object with a legacy array.
     *
     * @param array $filter legacy array of conditions used by Model->query().
     *
     * @return void
     */
    public function filter($filter)
    {
        $operators = [
            'eq'                    => self::OP_EQUAL,
            'gt'                    => self::OP_GREATER,
            'gte'                   => self::OP_GREATER_EQUAL,
            'lt'                    => self::OP_LESS,
            'lte'                   => self::OP_LESS_EQUAL,
            'ne'                    => self::OP_NOT_EQUAL,
            'in'                    => self::OP_IN,
            'not in'                => self::OP_NOT_IN,
            'is'                    => self::OP_IS,
            'is not'                => self::OP_IS_NOT,
            'like'                  => self::OP_LIKE,
            'match'                 => self::OP_MATCH,
            'match in boolean mode' => self::OP_MATCH_BOOLEAN_MODE,
        ];

        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $method => $key) {
                    $method = strtolower($method);
                    $this->condition($field, $key, isset($operators[$method]) ? $operators[$method] : self::OP_EQUAL);
                }
            } else {
                $this->condition($field, $value);
            }
        }

        return true;
    }
}
