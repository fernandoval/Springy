<?php

/**
 * Springy Exception.
 *
 * @copyright 2023 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Exceptions;

use RuntimeException;
use Throwable;

class SpringyException extends RuntimeException
{
    /**
     * Constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     * @param string|null    $file
     * @param int|null       $line
     */
    public function __construct(
        string $message,
        int $code = E_USER_ERROR,
        ?Throwable $previous = null,
        ?string $file = null,
        ?int $line = null
    ) {
        if (is_null($file) || is_null($line)) {
            $dbt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
            $file = $dbt[0]['file'] ?? '';
            $line = $dbt[0]['line'] ?? 0;
        }

        $this->file = $file;
        $this->line = $line;

        parent::__construct($message, $code, $previous);
    }
}
