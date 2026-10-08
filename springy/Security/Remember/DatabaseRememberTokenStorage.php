<?php

/**
 * "Remember me" token storage driver for relational databases.
 *
 * The table structure is in remember_tokens_create_table.sql. Databases do
 * not expire rows by themselves, so schedule deleteExpired() to keep the
 * table small.
 *
 * Any failure of the database connection or query is reported as a
 * RememberTokenStorageException, with the original exception as previous.
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy\Security\Remember;

use DateTimeImmutable;
use Springy\Database\Connection;
use Throwable;

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
        try {
            $this->connection->run(
                'INSERT INTO ' . $this->enclose($this->table) . ' ('
                . $this->enclose('selector') . ', '
                . $this->enclose('identity_id') . ', '
                . $this->enclose('validator_hash') . ', '
                . $this->enclose('expires_at')
                . ') VALUES (?, ?, ?, ?)',
                [$token->selector, $token->identityId, $token->validatorHash, $token->expiresAt->getTimestamp()]
            );
        } catch (Throwable $exception) {
            throw $this->createFailure('save the remember token', $exception);
        }
    }

    public function findBySelector(string $selector): ?RememberToken
    {
        try {
            $rows = $this->connection->select(
                'SELECT ' . $this->enclose('selector') . ', '
                . $this->enclose('identity_id') . ', '
                . $this->enclose('validator_hash') . ', '
                . $this->enclose('expires_at')
                . ' FROM ' . $this->enclose($this->table)
                . ' WHERE ' . $this->enclose('selector') . ' = ?',
                [$selector]
            );
        } catch (Throwable $exception) {
            throw $this->createFailure('read the remember token', $exception);
        }

        if ($rows === []) {
            return null;
        }

        return RememberToken::fromArray($rows[0]);
    }

    public function delete(string $selector): bool
    {
        try {
            return $this->connection->execute(
                'DELETE FROM ' . $this->enclose($this->table) . ' WHERE ' . $this->enclose('selector') . ' = ?',
                [$selector]
            ) > 0;
        } catch (Throwable $exception) {
            throw $this->createFailure('delete the remember token', $exception);
        }
    }

    public function deleteAllByIdentity(string $identityId): void
    {
        try {
            $this->connection->run(
                'DELETE FROM ' . $this->enclose($this->table) . ' WHERE ' . $this->enclose('identity_id') . ' = ?',
                [$identityId]
            );
        } catch (Throwable $exception) {
            throw $this->createFailure('delete the identity remember tokens', $exception);
        }
    }

    /**
     * Removes the expired tokens and returns how many were removed.
     *
     * @throws RememberTokenStorageException when the storage fails.
     */
    public function deleteExpired(DateTimeImmutable $now = new DateTimeImmutable()): int
    {
        try {
            return $this->connection->execute(
                'DELETE FROM ' . $this->enclose($this->table) . ' WHERE ' . $this->enclose('expires_at') . ' <= ?',
                [$now->getTimestamp()]
            );
        } catch (Throwable $exception) {
            throw $this->createFailure('delete the expired remember tokens', $exception);
        }
    }

    private function createFailure(string $action, Throwable $previous): RememberTokenStorageException
    {
        return new RememberTokenStorageException('Could not ' . $action . ' on the database.', previous: $previous);
    }

    private function enclose(string $identifier): string
    {
        return $this->connection->enclose($identifier);
    }
}
