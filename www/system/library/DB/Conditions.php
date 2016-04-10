<?php
/** \file
 *  Springy.
 *
 *  \brief      Child database class to construct conditions.
 *  \copyright  Copyright (c) 2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1
 *  \ingroup    framework
 */
namespace Springy\DB;

/**
 *  \brief Class to construct database conditions clauses.
 */
class Conditions
{
    private $conditions = [];
    private $parameters = [];

    const COND_AND = 'AND';
    const COND_OR = 'OR';

    const OP_EQUAL               = '=';
    const OP_NOT_EQUAL           = '!=';
    const OP_GREATER             = '>';
    const OP_GREATER_EQUAL       = '>=';
    const OP_LESS                = '<';
    const OP_LESS_EQUAL          = '<=';
    const OP_IN                  = 'IN';
    const OP_NOT_IN              = 'NOT IN';
    const OP_IS                  = 'IS';
    const OP_IS_NOT              = 'IS NOT';
    const OP_LIKE                = 'LIKE';
    const OP_NOT_LIKE            = 'NOT LIKE';
    const OP_MATCH               = 'MATCH';
    const OP_MATCH_BOOLEAN_MODE  = 'MATCH BOOLEAN';

    const OP_EQUAL_ALIAS         = 'EQ';
    const OP_NOT_EQUAL_ALIAS     = 'NE';
    const OP_GREATER_ALIAS       = 'GT';
    const OP_GREATER_EQUAL_ALIAS = 'GTE';
    const OP_LESS_ALIAS          = 'LT';
    const OP_LESS_EQUAL_ALIAS    = 'LTE';

    const EXPR_SIMPLE = 'exp';
    const EXPR_SUB    = 'sub';

    /**
     *  \brief The constructor method.
     */
    public function __construct($conditions = null)
    {
        $this->conditions = [];

        if (is_array($conditions)) {
            $this->conditions = $conditions;
        }
    }

    /**
     *  \brief Conver the condition array element to string form.
     *
     *  Will feed the array of parameters too.
     */
    private function condToString(array $condition, $ommitCondType = false)
    {
        if ($ommitCondType) {
            $condition['expression'] = '';
        }

        // Convert alias operator
        switch ($condition['operator']) {
            case self::OP_EQUAL_ALIAS:
                $condition['operator'] = self::OP_EQUAL;
                break;
            case self::OP_NOT_EQUAL_ALIAS:
                $condition['operator'] = self::OP_NOT_EQUAL;
                break;
            case self::OP_GREATER_ALIAS:
                $condition['operator'] = self::OP_GREATER;
                break;
            case self::OP_GREATER_EQUAL_ALIAS:
                $condition['operator'] = self::OP_GREATER_EQUAL;
                break;
            case self::OP_LESS_ALIAS:
                $condition['operator'] = self::OP_LESS;
                break;
            case self::OP_LESS_EQUAL_ALIAS:
                $condition['operator'] = self::OP_LESS_EQUAL;
                break;
        }

        switch ($condition['operator']) {
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
                return $condition['expression'].' '.$condition['column'].' '.$condition['operator'].' ?';
            case self::OP_IN:
            case self::OP_NOT_IN:
                $this->parameters = array_merge($this->parameters, $condition['value']);
                return $condition['expression'].' '.$condition['column'].($condition['operator'] === self::OP_NOT_IN ? ' NOT' : '').' IN ('.trim(str_repeat('?, ', count($condition['value'])), ', ').')';
            case self::OP_MATCH:
            case self::OP_MATCH_BOOLEAN_MODE:
                $this->parameters[] = $condition['value'];
                return $condition['expression'].' MATCH ('.$condition['column'].') AGAINST (?'.($condition['operator'] === self::OP_MATCH_BOOLEAN_MODE ? ' IN BOOLEAN MODE' : '').')';
        }

        return false;
    }

    /**
     *  \brief Convert the objet to a string in database conditions form.
     *
     *  The values of the parameter will be in question mark form and can be obtained with params() method.
     */
    public function __toString()
    {
        $this->parameters = [];
        $conditions = '';

        foreach ($this->conditions as $condition) {
            $condStr = '';

            if ($condition['type'] == self::EXPR_SIMPLE) {
                if (!$condStr = $this->condToString($condition, empty($conditions))) {
                    throw new Exception('Unknown condition operator.', 500);
                }

                $condStr = trim($condStr);
            } elseif ($condition['type'] == self::EXPR_SUB) {
                $sub = new self($condition['conditions']);
                $condStr = (empty($conditions) ? '' : 'AND ').'('.$sub->parse().')';
                $this->parameters = array_merge($this->parameters, $sub->params());
                unset($sub);
            } else {
                throw new Exception('Unknown expressiont type.', 500);
            }

            $conditions .= ' '.$condStr;
        }

        return trim($conditions);
    }

    /**
     *  \brief Clear the clause.
     */
    public function clear()
    {
        $this->conditions = [];
        $this->parameters = [];
    }

    /**
     *  \brief Add a condition to the clause.
     *
     *  If $column is an array of conditions made by other conditions object, this will create a set of
     *  conditions inside parentesis group.
     */
    public function condition($column, $value = null, $operator = self::OP_EQUAL, $expression = self::COND_AND)
    {
        if (is_array($column)) {
            $this->conditions[] = [
                'type'       => self::EXPR_SUB,
                'conditions' => $column,
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
     *  \brief Return the number of conditions.
     */
    public function count()
    {
        return count($this->conditions);
    }

    /**
     *  \brief Get the content of conditions in internal array form.
     *  \return An array of conditions.
     */
    public function get($column = null)
    {
        if (is_null($column)) {
            return $this->conditions;
        }

        foreach ($this->conditions as $condition) {
            if ($condition['column'] == $column) {
                return $condition;
            }
        }

        return false;
    }

    /**
     *  \brief Get the params after parse the clause.
     *  \return An array of parameters in same sequence of the question marks.
     */
    public function params()
    {
        return $this->parameters;
    }

    /**
     *  \brief An alias to __toString() method.
     */
    public function parse()
    {
        return $this->__toString();
    }

    /**
     *  \brief Populate object with legacy array.
     */
    public function filter($filter)
    {
        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $method => $key) {
                    switch (strtolower($method)) {
                        case 'eq':
                            $operator = self::OP_EQUAL;
                            break;
                        case 'gt':
                            $operator = self::OP_GREATER;
                            break;
                        case 'gte':
                            $operator = self::OP_GREATER_EQUAL;
                            break;
                        case 'lt':
                            $operator = self::OP_LESS;
                            break;
                        case 'lte':
                            $operator = self::OP_LESS_EQUAL;
                            break;
                        case 'ne':
                            $operator = self::OP_NOT_EQUAL;
                            break;
                        case 'in':
                            $operator = self::OP_IN;
                            break;
                        case 'not in':
                            $operator = self::OP_NOT_IN;
                            break;
                        case 'is':
                            $operator = self::OP_IS;
                            break;
                        case 'is not':
                            $operator = self::OP_IS_NOT;
                            break;
                        case 'like':
                            $operator = self::OP_LIKE;
                            break;
                        case 'match':
                            $operator = self::OP_MATCH;
                            break;
                        case 'match in boolean mode':
                            $operator = self::OP_MATCH_BOOLEAN_MODE;
                            break;
                        default:
                            $operator = self::OP_EQUAL;
                    }

                    $this->condition($field, $key, $operator);
                }
            } else {
                $this->condition($field, $value);
            }
        }

        return true;
    }
}
