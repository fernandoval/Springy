<?php

/**
 * In-memory "remember me" token storage used by tests.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use Springy\Security\Remember\RememberToken;
use Springy\Security\Remember\RememberTokenStorageInterface;

final class InMemoryRememberTokenStorage implements RememberTokenStorageInterface
{
    /** @var array<string, RememberToken> */
    public array $tokens = [];

    public function save(RememberToken $token): void
    {
        $this->tokens[$token->selector] = $token;
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        return $this->tokens[$selector] ?? null;
    }

    public function delete(string $selector): void
    {
        unset($this->tokens[$selector]);
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $this->tokens = array_filter(
            $this->tokens,
            fn (RememberToken $token): bool => $token->identityId !== $identityId
        );
    }
}
