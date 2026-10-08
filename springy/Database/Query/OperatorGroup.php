<?php

/**
 * Enum of operators for set of conditions.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Database\Query;

enum OperatorGroup
{
    case And;
    case Or;

    public function toString(): string
    {
        return $this === self::And ? ' AND ' : ' OR ';
    }
}
