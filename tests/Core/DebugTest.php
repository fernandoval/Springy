<?php

/**
 * Test case for the class Springy\Core\Debug.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.4
 */

use PHPUnit\Framework\TestCase;
use Springy\Core\Debug;

class DebugTest extends TestCase
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
        $this->assertMatchesRegularExpression('/^(.+)echo(.+)"teste"(.+)$/m', Debug::printRc('echo "teste"'));
    }
}
