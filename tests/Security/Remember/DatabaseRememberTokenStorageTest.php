<?php

/**
 * Test case for Security\Remember\DatabaseRememberTokenStorage class.
 *
 * Requires the MySQL server configured by DB_HOST environment variable.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

require_once __DIR__ . '/RememberTokenStorageTestCase.php';

use Springy\Database\Connection;
use Springy\Security\Remember\DatabaseRememberTokenStorage;
use Springy\Security\Remember\RememberTokenStorageInterface;

class DatabaseRememberTokenStorageTest extends RememberTokenStorageTestCase
{
    private const TABLE = '_remember_tokens';

    private Connection $connection;

    protected function createStorage(): RememberTokenStorageInterface
    {
        $host = getenv('DB_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('DB_HOST environment variable is not defined.');
        }

        $this->connection = new Connection('mysql');
        $this->connection->run('DROP TABLE IF EXISTS `' . self::TABLE . '`');
        $this->connection->run(file_get_contents(
            __DIR__ . '/../../../springy/Security/Remember/remember_tokens_create_table.sql'
        ));

        return new DatabaseRememberTokenStorage($this->connection, self::TABLE);
    }

    public function testThatDeleteExpiredRemovesOnlyExpiredTokens()
    {
        $identityId = $this->createIdentityId();
        $expired = $this->createToken($identityId, '-1 second');
        $valid = $this->createToken($identityId);
        $this->storage->save($expired);
        $this->storage->save($valid);

        $this->assertSame(1, $this->storage->deleteExpired());
        $this->assertNull($this->storage->findBySelector($expired->selector));
        $this->assertNotNull($this->storage->findBySelector($valid->selector));
    }

    public function testThatOnlyTheValidatorHashIsStored()
    {
        $token = $this->createToken($this->createIdentityId());
        $this->storage->save($token);

        $rows = $this->connection->select('SELECT * FROM `' . self::TABLE . '`');

        $this->assertCount(1, $rows);
        $this->assertSame($token->validatorHash, $rows[0]['validator_hash']);
        $this->assertSame($token->identityId, $rows[0]['identity_id']);
        $this->assertSame($token->expiresAt->getTimestamp(), (int) $rows[0]['expires_at']);
    }
}
