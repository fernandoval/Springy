<?php

/**
 * Database condition clauses constructor.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Query;

use Springy\Exceptions\SpringyException;

/**
 * Database condition clauses constructor class.
 */
class Condition
{
    /**
     * Constructor.
     *
     * If $valueIsColumn is set to true, the value will be a column name or a
     * function.
     */
    public function __construct(
        protected string $column,
        protected mixed $value,
        protected OperatorComparation $operator = OperatorComparation::Equal,
        protected bool $valueIsColumn = false
    ) {
    }

    /**
     * Converts the condition object to its string form.
     *
     * @throws SpringyException
     *
     * @return string
     */
    public function __toString(): string
    {
        return match ($this->operator) {
            OperatorComparation::Equal,
            OperatorComparation::NotEqual,
            OperatorComparation::Greater,
            OperatorComparation::GreaterEqual,
            OperatorComparation::Less,
            OperatorComparation::LessEqual,
            OperatorComparation::Is,
            OperatorComparation::IsNot,
            OperatorComparation::Like,
            OperatorComparation::NotLike => $this->comparationGeneral(),
            OperatorComparation::In,
            OperatorComparation::NotIn => $this->comparationIn(),
            OperatorComparation::Match,
            OperatorComparation::MatchBooleanMode => $this->comparationMatch(),
            default => throw new SpringyException('Unknown condition operator.'),
        };
    }

    /**
     * Builds a general comparation string.
     *
     * @return string
     */
    protected function comparationGeneral(): string
    {
        return $this->column . $this->operator->toString() . $this->getQuestionMark();
    }

    /**
     * Builds a comparation string for IN and NOT IN condition.
     *
     * @return string
     */
    protected function comparationIn(): string
    {
        return $this->column
            . $this->operator->toString()
            . '('
            . trim(str_repeat('?, ', count($this->value)), ', ')
            . ')';
    }

    /**
     * Builds a comparation string for MATCH condition.
     *
     * The MATCH condition is used to performs filters for
     * FULLTEXT indexes in MySQL tables.
     *
     * @return string
     */
    protected function comparationMatch(): string
    {
        return 'MATCH (' . $this->column . ') AGAINST (' . $this->getQuestionMark() . (
            $this->operator === OperatorComparation::MatchBooleanMode ? ' IN BOOLEAN MODE' : ''
        ) . ')';
    }

    /**
     * Gets the question mark or value property as a field name.
     *
     * @return string
     */
    protected function getQuestionMark(): string
    {
        return $this->valueIsColumn ? $this->value : '?';
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): OperatorComparation
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Returns true if the value is a column name or a function instead of a parameter.
     */
    public function isValueColumn(): bool
    {
        return $this->valueIsColumn;
    }
}
