<?php

/**
 * Test case for Security\BCryptHasher class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.1.0
 */

use PHPUnit\Framework\TestCase;
use Springy\Security\BCryptHasher;

class BCryptHasherTest extends TestCase
{
    public BCryptHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new BCryptHasher();
    }

    public function testThatHasherCanGenerateASecureHash()
    {
        $hash = $this->hasher->make('password');

        $this->assertGreaterThanOrEqual(60, strlen($hash));
    }

    public function testThatHasherCanVerifyTheHashedString()
    {
        $hash = $this->hasher->make('password');

        $this->assertTrue($this->hasher->verify('password', $hash));
    }

    public function testThatHasherTellsIfAHashNeedsRehashing()
    {
        $hash = $this->hasher->make('password', 5);

        $this->assertTrue($this->hasher->needsRehash($hash, 10));
    }
}
