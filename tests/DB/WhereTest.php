<?php
/**
 * Test case for DB\Where class.
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0.2
 */
use PHPUnit\Framework\TestCase;
use Springy\DB\Where;

class WhereTest extends TestCase
{
    protected $where;

    protected function setUp(): void
    {
        $this->where = new Where();
        $this->where->condition('column_a', 0);
        $this->where->condition('column_b', 'none');
    }

    public function testParse()
    {
        $string = $this->where->parse();
        $this->assertStringStartsWith(' WHERE ', $string);
    }

    public function testToString()
    {
        $string = (string) $this->where;
        $this->assertStringStartsWith(' WHERE ', $string);
    }
}
