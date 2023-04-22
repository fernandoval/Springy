<?php

/**
 * Springy Exception.
 *
 * @copyright 2023 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 *
 * @version   1.0.0
 */

namespace Springy\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Springy Exception class.
 */
class SpringyException extends RuntimeException
{
    /** @var array|null error context */
    protected $context;

    /**
     * Constructor.
     *
     * @param int        $code
     * @param string     $message
     * @param string     $file
     * @param int        $line
     * @param array|null $context
     */
    public function __construct(
        string $message = null,
        int $code = E_USER_ERROR,
        Throwable $previous = null,
        string $file = null,
        int $line = null
    ) {
        if (is_null($file) || is_null($line)) {
            $dbt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
            $file = $dbt[0]['file'];
            $line = $dbt[0]['line'];
        }

        $this->file = $file;
        $this->line = $line;

        parent::__construct($message, $code, $previous);
    }
}
