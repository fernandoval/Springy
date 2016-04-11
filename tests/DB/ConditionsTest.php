<?php
/** \file
 *  Springy.
 *
 *  \brief      Test case for DB\Conditions class.
 *  \copyright  Copyright ₢ 2016 Fernando Val.
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \version    0.1
 *  \ingroup    tests
 */
use Springy\DB\Conditions;

class ConditionsTest extends PHPUnit_Framework_TestCase
{
    protected $conditions;

    public function setUp()
    {
        $this->conditions = new Conditions();
        $this->conditions->condition('column_a', 0);
        $this->conditions->condition('column_b', 'none');
    }

    public function testClear()
    {
        $this->conditions->clear();
        $this->assertCount(0, $this->conditions->get());
    }

    public function testCount()
    {
        $this->assertEquals(2, $this->conditions->count());
    }

    public function testFilter()
    {
        $this->conditions->clear();
        $this->conditions->filter(['column_a' => 0, 'column_b' => 'none']);
        $this->assertEquals(2, $this->conditions->count());
        $this->assertArrayHasKey('column', $this->conditions->get('column_a'));
        $this->assertArrayHasKey('column', $this->conditions->get('column_b'));
    }

    public function testGet()
    {
        $this->assertArrayHasKey('column', $this->conditions->get('column_a'));
        $this->assertArrayHasKey('column', $this->conditions->get('column_b'));
        $this->assertCount(2, $this->conditions->get());
    }
    
    public function testParams()
    {
        $this->conditions->parse();
        $this->assertCount(2, $this->conditions->params());
        $this->assertContains('none', $this->conditions->params());
    }
    
    public function testParse()
    {
        $string = $this->conditions->parse();
        $this->assertStringStartsWith('column_a = ?', $string);
        $this->assertStringEndsWith('column_b = ?', $string);
        $this->assertStringEndsWith('column_b = ?', $string);
    }
    
    public function testToString()
    {
        $string = (string) $this->conditions;
        $this->assertStringStartsWith('column_a = ?', $string);
        $this->assertStringEndsWith('column_b = ?', $string);
        $this->assertStringEndsWith('column_b = ?', $string);
    }
}
