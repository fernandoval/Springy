<?php
/**	\file
 *	Springy.
 *
 *	\brief      Test case for the class Springy\Copyright.
 *  \copyright  (c) 2016 Fernando Val.
 *  \author     Fernando Val - fernando.val@gmail.com
 *	\version    0.1.0.1
 *	\ingroup    tests
 */
use Springy\Core\Copyright;

class CopyrightTest extends PHPUnit_Framework_TestCase
{
    public function testPrintCopyright()
    {
        $this->expectOutputRegex('/^<!DOCTYPE html>\n.+$/m');
        
        $copyright = new Copyright(false);
        $copyright->printCopyright();
    }
}
