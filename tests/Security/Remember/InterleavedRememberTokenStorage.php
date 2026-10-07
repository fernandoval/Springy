<?php

/**
 * "Remember me" token storage decorator that runs a callback right before an
 * operation, simulating a concurrent request between two steps.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use Springy\Security\Remember\RememberToken;
use Springy\Security\Remember\RememberTokenStorageInterface;

final class InterleavedRememberTokenStorage implements RememberTokenStorageInterface
{
    /** @var array<string, callable> callbacks by operation name, each one runs only once. */
    private array $callbacks = [];

    /** @var string[] selectors of every saved token. */
    public array $savedSelectors = [];

    public function __construct(private readonly RememberTokenStorageInterface $storage)
    {
    }

    /**
     * Runs the callback before the next call of the operation (save, findBySelector, delete...).
     */
    public function before(string $operation, callable $callback): void
    {
        $this->callbacks[$operation] = $callback;
    }

    public function save(RememberToken $token): void
    {
        $this->interleave('save');
        $this->storage->save($token);
        $this->savedSelectors[] = $token->selector;
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        $this->interleave('findBySelector');

        return $this->storage->findBySelector($selector);
    }

    public function delete(string $selector): bool
    {
        $this->interleave('delete');

        return $this->storage->delete($selector);
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $this->interleave('deleteAllByIdentity');
        $this->storage->deleteAllByIdentity($identityId);
    }

    private function interleave(string $operation): void
    {
        $callback = $this->callbacks[$operation] ?? null;
        unset($this->callbacks[$operation]);

        if ($callback !== null) {
            $callback();
        }
    }
}
