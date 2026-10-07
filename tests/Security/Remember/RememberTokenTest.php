<?php

/**
 * Test case for Security\Remember\RememberToken class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Security\Remember\RememberToken;
use Springy\Security\Remember\RememberTokenCredential;
use Springy\Security\Remember\RememberTokenStorageException;

class RememberTokenTest extends TestCase
{
    private function createToken(RememberTokenCredential $credential, string $expiresAt = '+1 hour'): RememberToken
    {
        return new RememberToken(
            selector: $credential->selector,
            validatorHash: $credential->getValidatorHash(),
            identityId: '42',
            expiresAt: new DateTimeImmutable($expiresAt),
        );
    }

    public function testThatTokenRecognizesItsValidator()
    {
        $credential = RememberTokenCredential::generate();
        $token = $this->createToken($credential);

        $this->assertTrue($token->hasValidator($credential->validator));
        $this->assertFalse($token->hasValidator(RememberTokenCredential::generate()->validator));
        $this->assertFalse($token->hasValidator($credential->getValidatorHash()));
    }

    public function testThatExpirationIsChecked()
    {
        $credential = RememberTokenCredential::generate();
        $now = new DateTimeImmutable('2026-01-01 12:00:00');
        $token = new RememberToken($credential->selector, $credential->getValidatorHash(), '42', $now);

        $this->assertFalse($token->isExpired($now->modify('-1 second')));
        $this->assertTrue($token->isExpired($now));
        $this->assertTrue($token->isExpired($now->modify('+1 second')));
        $this->assertTrue($this->createToken($credential, '-1 second')->isExpired());
        $this->assertFalse($this->createToken($credential)->isExpired());
    }

    public function testThatSecondsToExpireNeverIsNegative()
    {
        $credential = RememberTokenCredential::generate();
        $now = new DateTimeImmutable('2026-01-01 12:00:00');
        $token = new RememberToken($credential->selector, $credential->getValidatorHash(), '42', $now);

        $this->assertSame(60, $token->getSecondsToExpire($now->modify('-60 seconds')));
        $this->assertSame(0, $token->getSecondsToExpire($now));
        $this->assertSame(0, $token->getSecondsToExpire($now->modify('+60 seconds')));
    }

    public function testThatArrayRoundTripKeepsValues()
    {
        $token = $this->createToken(RememberTokenCredential::generate());
        $restored = RememberToken::fromArray($token->toArray());

        $this->assertSame($token->toArray(), $restored->toArray());
        $this->assertSame('42', $restored->identityId);
    }

    public function testThatIncompleteArrayIsRejected()
    {
        $data = $this->createToken(RememberTokenCredential::generate())->toArray();
        unset($data['validator_hash']);

        $this->expectException(RememberTokenStorageException::class);

        RememberToken::fromArray($data);
    }
}
