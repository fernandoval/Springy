<?php

/**
 * Test case for the class Springy\Core\Copyright.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Core\Copyright;

class CopyrightTest extends TestCase
{
    public function testPrintCopyright()
    {
        $this->expectOutputRegex('/^<!DOCTYPE html>\n.+$/m');

        new Copyright(true);
    }
}
