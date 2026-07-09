<?php

/**
 * HTTP error.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Exceptions;

use Error;
use Throwable;

class HttpError extends Error
{
    public function __construct(
        string $message = 'Internal Server Error',
        int $code = 500,
        ?Throwable $previous = null
    ) {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $this->file = $dbt[0]['file'] ?? '';
        $this->line = $dbt[0]['line'] ?? 0;

        parent::__construct($message, $code, $previous);
    }
}
