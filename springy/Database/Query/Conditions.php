<?php

/**
 * Database conditions clauses constructor class.
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Query;

/**
 * Database conditions clauses constructor class.
 */
class Conditions
{
    /** @var array the conditions */
    protected array $conditions;
    /** @var array of ? parameters for prepare */
    protected array $parameters;
    protected array $unions;

    public function __construct(Condition|self|null $condition = null)
    {
        $this->clear();

        if (!is_null($condition)) {
            $this->add($condition);
        }
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
        $result = '';

        foreach ($this->conditions as $index => $condition) {
            $result .= (empty($result) ? '' : $this->getUnion($index)->toString())
                . ($condition instanceof self ? '(' . $condition->parse() . ')' : $condition);

            if ($condition instanceof self) {
                $this->addParameters($condition->params());
            } elseif (!$condition->isValueColumn()) {
                $this->addParameters($condition->getValue());
            }
        }

        return trim($result);
    }

    protected function getUnion(int $index): OperatorGroup
    {
        return $this->unions[$index] ?? OperatorGroup::And;
    }

    /**
     * Adds the condition value to the parameters array.
     */
    protected function addParameters(mixed $value): void
    {
        if (is_array($value)) {
            $this->parameters = array_merge($this->parameters, $value);

            return;
        }

        $this->parameters[] = $value;
    }

    /**
     * Adds a condition or a subset of conditions to the conditions list.
     */
    public function add(Condition|self $condition, OperatorGroup $expression = OperatorGroup::And): self
    {
        $this->conditions[] = $condition;
        $this->unions[] = $expression;

        return $this;
    }

    /**
     * Adds a column condition to the conditions list.
     *
     * If $compareCols is set to true, the value will be a column name or a
     * function.
     */
    public function addColumn(
        string $column,
        mixed $value = null,
        OperatorComparation $operator = OperatorComparation::Equal,
        OperatorGroup $expression = OperatorGroup::And,
        bool $compareCols = false
    ): self {
        return $this->add(new Condition($column, $value, $operator, $compareCols), $expression);
    }

    /**
     * Clears the clause.
     */
    public function clear(): self
    {
        $this->conditions = [];
        $this->parameters = [];
        $this->unions = [];

        return $this;
    }

    /**
     * Returns the number of conditions.
     */
    public function count(): int
    {
        return count($this->conditions);
    }

    /**
     * Finds a column in the array of conditions.
     *
     * @return Condition|false the condition for given column or false if not found.
     */
    protected function find(string $column, array $conditions): Condition|false
    {
        foreach ($conditions as $condition) {
            if ($condition instanceof Condition && $condition->getColumn() === $column) {
                return $condition;
            } elseif ($condition instanceof self) {
                $result = $condition->find($column, $condition->get());

                if ($result instanceof Condition) {
                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * Gets the content of conditions in internal array form.
     *
     * @param string|null $column the name of the column or null to all conditions.
     */
    public function get(?string $column = null): array|Condition|false
    {
        return is_null($column) ? $this->conditions : $this->find($column, $this->conditions);
    }

    /**
     * Gets the params after parse the clause.
     *
     * @return array of parameters in same sequence of the question marks into conditional string.
     */
    public function params(): array
    {
        return $this->parameters;
    }

    /**
     * An alias to __toString() method.
     */
    public function parse(): string
    {
        return $this->__toString();
    }

    /**
     * Removes all ocurrences of the column in the conditions.
     */
    public function remove(string $column): self
    {
        foreach ($this->conditions as $index => $condition) {
            if ($condition instanceof Condition && $condition->getColumn() == $column) {
                unset($this->conditions[$index]);
                unset($this->unions[$index]);
            } elseif ($condition instanceof self) {
                $condition->remove($column);
            }
        }

        return $this;
    }
}
