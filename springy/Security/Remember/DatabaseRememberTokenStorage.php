<?php

/**
 * "Remember me" token storage driver for relational databases.
 *
 * The table structure is in remember_tokens_create_table.sql. Databases do
 * not expire rows by themselves, so schedule deleteExpired() to keep the
 * table small.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use DateTimeImmutable;
use Springy\Database\Connection;

final class DatabaseRememberTokenStorage implements RememberTokenStorageInterface
{
    public const DEFAULT_TABLE = '_remember_tokens';

    public function __construct(
        private readonly Connection $connection,
        private readonly string $table = self::DEFAULT_TABLE,
    ) {
    }

    public function save(RememberToken $token): void
    {
        $this->connection->run(
            'INSERT INTO ' . $this->enclose($this->table) . ' ('
            . $this->enclose('selector') . ', '
            . $this->enclose('identity_id') . ', '
            . $this->enclose('validator_hash') . ', '
            . $this->enclose('expires_at')
            . ') VALUES (?, ?, ?, ?)',
            [$token->selector, $token->identityId, $token->validatorHash, $token->expiresAt->getTimestamp()]
        );
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        $rows = $this->connection->select(
            'SELECT ' . $this->enclose('selector') . ', '
            . $this->enclose('identity_id') . ', '
            . $this->enclose('validator_hash') . ', '
            . $this->enclose('expires_at')
            . ' FROM ' . $this->enclose($this->table)
            . ' WHERE ' . $this->enclose('selector') . ' = ?',
            [$selector]
        );

        if ($rows === []) {
            return null;
        }

        return RememberToken::fromArray($rows[0]);
    }

    public function delete(string $selector): bool
    {
        return $this->connection->execute(
            'DELETE FROM ' . $this->enclose($this->table) . ' WHERE ' . $this->enclose('selector') . ' = ?',
            [$selector]
        ) > 0;
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        $this->connection->run(
            'DELETE FROM ' . $this->enclose($this->table) . ' WHERE ' . $this->enclose('identity_id') . ' = ?',
            [$identityId]
        );
    }

    /**
     * Removes the expired tokens and returns how many were removed.
     */
    public function deleteExpired(DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        return $this->connection->execute(
            'DELETE FROM ' . $this->enclose($this->table) . ' WHERE ' . $this->enclose('expires_at') . ' <= ?',
            [$now->getTimestamp()]
        );
    }

    private function enclose(string $identifier): string
    {
        return $this->connection->enclose($identifier);
    }
}
