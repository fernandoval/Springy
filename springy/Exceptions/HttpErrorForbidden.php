<?php

/**
 * HTTP 403 Forbidden error.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Exceptions;

use Throwable;

class HttpErrorForbidden extends HttpError
{
    public function __construct(
        string $message = 'Forbidden',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
