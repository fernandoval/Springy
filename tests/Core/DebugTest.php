<?php
/**	\file
 *	Springy.
 *
 *	\brief      Test case for the class Springy\Debug.
 *  \copyright  (c) 2016 Fernando Val.
 *  \author     Fernando Val - fernando.val@gmail.com
 *	\version    0.1.0.1
 *	\ingroup    tests
 */
use Springy\Core\Debug;

class DebugTest extends PHPUnit_Framework_TestCase
{
    public function testAddAndGet()
    {
        $this->assertEmpty(Debug::get());

        debug('PHPUnit Test');
        $this->assertNotEmpty(Debug::get());
    }

    public function testPrintOut()
    {
        $this->expectOutputString('');
        Debug::printOut();
    }

    public function testPrintRC()
    {
        $this->assertRegExp('/^(.+)echo(.+)"teste"(.+)$/m', Debug::print_rc('echo "teste"'));
    }
}
