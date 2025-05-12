<?php

/**
 * HTTP 404 Not Found error class.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Exceptions;

use Throwable;

class HttpErrorNotFound extends HttpError
{
    public function __construct(
        string $message = 'Not Found',
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
