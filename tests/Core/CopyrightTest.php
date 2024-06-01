<?php

/**
 * Test case for the class Springy\Core\Copyright.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version    1.0.0.4
 */

use PHPUnit\Framework\TestCase;
use Springy\Core\Copyright;

class CopyrightTest extends TestCase
{
    public function testPrintCopyright()
    {
        $this->expectOutputRegex('/^<!DOCTYPE html>\n.+$/m');

        $copyright = new Copyright(false);
        $copyright->printCopyright();
    }
}
