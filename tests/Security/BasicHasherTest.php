<?php

/**
 * Test case for Security\BasicHasher class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Springy\Security\BasicHasher;
use Springy\Security\HasherInterface;

class BasicHasherTest extends TestCase
{
    public BasicHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new BasicHasher();
    }

    public static function knownHashesProvider(): array
    {
        return [
            'lowercase' => ['password', 'BwcBVAFUBlcBBwcGAARQAwQGXQRQDgYGVlsOA1oCDV8='],
            'capitalized' => ['Password', 'VgIDBFVSVwMBA1AGBwBRBABXVgBXC1NbBlIEUwpdAlI='],
            'empty' => ['', 'BQBSAg9VXAtdUAgJUwoDBF0LAVUCDw0BU1QACgUGAAQ='],
            'multibyte' => ['ção€', 'UwAEAVNTBVVXAAUAB1NVAVFRV1MAAFlQUVNbVFECVQI='],
        ];
    }

    public function testThatHasherImplementsHasherInterface()
    {
        $this->assertInstanceOf(HasherInterface::class, $this->hasher);
    }

    public function testThatHasherGeneratesABase64EncodedHash()
    {
        $hash = $this->hasher->make('password');

        $this->assertSame(44, strlen($hash));
        $this->assertSame(32, strlen(base64_decode($hash, true)));
    }

    #[DataProvider('knownHashesProvider')]
    public function testThatHasherGeneratesTheExpectedHash(string $string, string $expected)
    {
        $this->assertSame($expected, $this->hasher->make($string));
        $this->assertSame($expected, $this->hasher->generateHash($string));
    }

    public function testThatHasherIsDeterministic()
    {
        $this->assertSame($this->hasher->make('password'), $this->hasher->make('password'));
        $this->assertSame($this->hasher->make('password'), (new BasicHasher())->make('password'));
    }

    public function testThatHasherIgnoresTheTimesArgument()
    {
        $this->assertSame($this->hasher->make('password', 5), $this->hasher->make('password', 20));
        $this->assertSame($this->hasher->generateHash('password', 1), $this->hasher->generateHash('password'));
    }

    public function testThatDifferentStringsGenerateDifferentHashes()
    {
        $this->assertNotSame($this->hasher->make('password'), $this->hasher->make('password1'));
    }

    public function testThatHasherIsCaseSensitive()
    {
        $this->assertNotSame($this->hasher->make('password'), $this->hasher->make('Password'));
        $this->assertFalse($this->hasher->verify('PASSWORD', $this->hasher->make('password')));
    }

    #[DataProvider('knownHashesProvider')]
    public function testThatHasherCanVerifyTheHashedString(string $string, string $hash)
    {
        $this->assertTrue($this->hasher->verify($string, $hash));
        $this->assertTrue($this->hasher->verify($string, $this->hasher->make($string)));
    }

    public function testThatHasherRejectsAWrongString()
    {
        $hash = $this->hasher->make('password');

        $this->assertFalse($this->hasher->verify('wrong password', $hash));
        $this->assertFalse($this->hasher->verify('', $hash));
        $this->assertFalse($this->hasher->verify('password ', $hash));
    }

    public function testThatHasherRejectsAnInvalidHash()
    {
        $this->assertFalse($this->hasher->verify('password', ''));
        $this->assertFalse($this->hasher->verify('password', 'invalid-hash'));
        $this->assertFalse($this->hasher->verify('password', password_hash('password', PASSWORD_BCRYPT)));
    }

    public function testThatHasherNeverNeedsRehashing()
    {
        $hash = $this->hasher->make('password');

        $this->assertFalse($this->hasher->needsRehash($hash));
        $this->assertFalse($this->hasher->needsRehash($hash, 5));
        $this->assertFalse($this->hasher->needsRehash($hash, 20));
        $this->assertFalse($this->hasher->needsRehash('any string'));
    }
}
