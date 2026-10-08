<?php

/**
 * Enum of operators for comparation conditions.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Query;

enum OperatorComparation: string
{
    case Equal = '=';
    case NotEqual = '!=';
    case Greater = '>';
    case GreaterEqual = '>=';
    case Less = '<';
    case LessEqual = '<=';
    case In = 'IN';
    case NotIn = 'NOT IN';
    case Is = 'IS';
    case IsNot = 'IS NOT';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
    case Match = 'MATCH';
    case MatchBooleanMode = 'MATCH BOOLEAN';

    public function toString(): string
    {
        return ' ' . $this->value . ' ';
    }
}
