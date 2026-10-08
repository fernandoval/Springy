<?php

/**
 * Database lost connection detector trait.
 *
 * Inspired in Laravel's Illuminate\Database\DetectsLostConnections class.
 */

namespace Springy\Database;

use Throwable;

trait LostConnectionDetector
{
    /**
     * Determine if the given exception was caused by a lost connection.
     */
    protected function isLostConnection(Throwable $err): bool
    {
        $messages = file(
            __DIR__ . '/LostConnectionMessages.txt',
            FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES
        );

        foreach ($messages as $message) {
            if (str_contains($err->getMessage(), $message)) {
                return true;
            }
        }

        return false;
    }
}
