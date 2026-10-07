<?php

/**
 * Test case for Security\Remember\RememberTokenCredential class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Springy\Security\Remember\InvalidRememberTokenException;
use Springy\Security\Remember\RememberTokenCredential;

class RememberTokenCredentialTest extends TestCase
{
    public function testThatGeneratedCredentialHasExpectedFormat()
    {
        $credential = RememberTokenCredential::generate();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{24}$/', $credential->selector);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $credential->validator);
        $this->assertSame($credential->selector . ':' . $credential->validator, $credential->toString());
    }

    public function testThatGeneratedCredentialsAreUnique()
    {
        $first = RememberTokenCredential::generate();
        $second = RememberTokenCredential::generate();

        $this->assertNotSame($first->selector, $second->selector);
        $this->assertNotSame($first->validator, $second->validator);
    }

    public function testThatStringRoundTripKeepsValues()
    {
        $credential = RememberTokenCredential::generate();
        $parsed = RememberTokenCredential::fromString($credential->toString());

        $this->assertEquals($credential, $parsed);
    }

    public function testThatValidatorHashIsSha256OfTheValidator()
    {
        $credential = RememberTokenCredential::generate();

        $this->assertSame(hash('sha256', $credential->validator), $credential->getValidatorHash());
        $this->assertNotSame($credential->validator, $credential->getValidatorHash());
    }

    public static function malformedValues(): array
    {
        $selector = str_repeat('a', 24);
        $validator = str_repeat('b', 64);

        return [
            'empty' => [''],
            'legacy user id' => ['42'],
            'missing separator' => [$selector . $validator],
            'short selector' => [substr($selector, 1) . ':' . $validator],
            'short validator' => [$selector . ':' . substr($validator, 1)],
            'uppercase' => [strtoupper($selector) . ':' . $validator],
            'non hex' => [str_repeat('z', 24) . ':' . $validator],
            'trailing newline' => [$selector . ':' . $validator . "\n"],
            'extra part' => [$selector . ':' . $validator . ':x'],
        ];
    }

    #[DataProvider('malformedValues')]
    public function testThatMalformedValuesAreRejected(string $value)
    {
        $this->expectException(InvalidRememberTokenException::class);

        RememberTokenCredential::fromString($value);
    }
}
