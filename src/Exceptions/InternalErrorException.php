<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Throwable;

/**
 * Exception thrown when an unexpected internal error occurs during RPC request processing.
 *
 * Represents JSON-RPC error code -32603, indicating a server-side error that is not
 * related to the request format or parameters. This exception wraps underlying system
 * exceptions and formats them according to the JSON-RPC error response specification.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InternalErrorException extends AbstractRequestException
{
    /**
     * Creates an internal error exception from an underlying throwable.
     *
     * Wraps any system exception into a JSON-RPC compliant error response with
     * error code -32603 (Internal error). The original exception message is
     * preserved in the error details for debugging purposes.
     *
     * @param  Throwable $exception the underlying exception that caused the internal error,
     *                              typically from failed business logic, database errors,
     *                              or other unexpected system failures during request
     *                              processing that should be reported to the client
     * @return self      a new instance containing the formatted error with HTTP 500 status,
     *                   JSON-RPC error code, and the original exception message in the
     *                   error details array for client-side error handling
     */
    public static function create(Throwable $exception): self
    {
        return self::new(-32_603, 'Internal error', [
            [
                'status' => '500',
                'title' => 'Internal error',
                'detail' => $exception->getMessage(),
            ],
        ]);
    }
}
