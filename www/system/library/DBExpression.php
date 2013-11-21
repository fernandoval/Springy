<?php
abstract class DBExpression {
	const C_ELSE = 'ELSE';
	const COND_AND = 'AND';
	const COND_OR  = 'OR';
	const DISTINCT = 'DISTINCT';
	const C_CASE = 'CASE';
	const SUM = 'SUM';
	const MAX = 'MAX';
	const CONCAT = '||';
	const COUNT = 'count';
	const ROUND = 'round';
	const LOWER = 'LOWER(%s)';
    const EQUAL = '=';
    const NOT_EQUAL = '!=';
    const ALT_NOT_EQUAL = '!=';
    const GREATER_THAN = '>';
    const LESS_THAN = '<';
    const GREATER_EQUAL = '>=';
    const LESS_EQUAL = '<=';
    const LIKE = 'LIKE';
    const NOT_LIKE = 'NOT LIKE';
    const ILIKE = 'ILIKE';
    const NOT_ILIKE = 'NOT ILIKE';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const BINARY_AND = '&';
    const BINARY_OR = '|';
    const ISNULL = 'IS NULL';
    const ISNOTNULL = 'IS NOT NULL';
	const RAND = 'RANDOM()';
}
?>