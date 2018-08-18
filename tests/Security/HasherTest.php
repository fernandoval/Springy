<?php
/**
 * Test case for Security\BCryptHasher class.
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0.2
 */
use PHPUnit\Framework\TestCase;
use Springy\Security\BCryptHasher as Hasher;

class HasherTest extends TestCase
{
    public $hasher;

    public function setUp()
    {
        $this->hasher = new Hasher();
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
