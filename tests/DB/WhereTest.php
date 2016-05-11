<?php
/** \file
 *  Springy.
 *
 *  \brief      Test case for DB\Where class.
 *  \copyright  Copyright ₢ 2016 Fernando Val.
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \version    0.1
 *  \ingroup    tests
 */
use Springy\DB\Where;

class WhereTest extends PHPUnit_Framework_TestCase
{
    protected $where;

    public function setUp()
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
