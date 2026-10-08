<?php

/**
 * Test case for Springy\Database\LostConnectionDetector trait.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Database\LostConnectionDetector;

class LostConnectionDetectorTest extends TestCase
{
    private object $detector;

    protected function setUp(): void
    {
        $this->detector = new class () {
            use LostConnectionDetector {
                isLostConnection as public;
            }
        };
    }

    public function testThatMessageFragmentInsidePdoMessageIsDetected()
    {
        $err = new PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away');

        $this->assertTrue($this->detector->isLostConnection($err));
    }

    public function testThatExactMessageIsDetected()
    {
        $this->assertTrue($this->detector->isLostConnection(new PDOException('Broken pipe')));
    }

    public function testThatSqlServerUnrecoverableConnectionIsDetected()
    {
        $err = new PDOException(
            'SQLSTATE[08S02]: [Microsoft][ODBC Driver 18 for SQL Server]The connection is broken and recovery is'
            . ' not possible. The connection is marked by the client driver as unrecoverable. No attempt was made'
            . ' to restore the connection.'
        );

        $this->assertTrue($this->detector->isLostConnection($err));
    }

    public function testThatWindowsSocketTimeoutIsDetected()
    {
        $err = new PDOException(
            'SQLSTATE[HY000] [2002] A connection attempt failed because the connected party did not properly'
            . ' respond after a period of time, or established connection failed because connected host has'
            . ' failed to respond.'
        );

        $this->assertTrue($this->detector->isLostConnection($err));
    }

    public function testThatOtherErrorsAreNotDetected()
    {
        $err = new PDOException("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'test.foo' doesn't exist");

        $this->assertFalse($this->detector->isLostConnection($err));
    }
}
