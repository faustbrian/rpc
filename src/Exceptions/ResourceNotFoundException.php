<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Override;

/**
 * Exception thrown when a requested resource cannot be found.
 *
 * This exception represents a JSON-RPC server error that maps to HTTP 404,
 * indicating that the requested resource (model, entity, or record) does not
 * exist in the system. The exception uses JSON:API error format to provide
 * structured error information to the client.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ResourceNotFoundException extends AbstractRequestException
{
    /**
     * Create a new resource not found exception instance.
     *
     * @param  null|string $detail Optional detailed explanation of why the resource
     *                             was not found. If not provided, uses a default message
     *                             indicating the requested model could not be found.
     *                             This detail is included in the JSON:API error response.
     * @return self        The created exception instance with JSON-RPC error code -32000
     */
    public static function create(?string $detail = null): self
    {
        return self::new(-32_000, 'Server error', [
            [
                'status' => '404',
                'title' => 'Resource Not Found',
                'detail' => $detail ?? 'The requested model could not be found.',
            ],
        ]);
    }

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTP 404 Not Found status code
     */
    #[Override()]
    public function getStatusCode(): int
    {
        return 404;
    }
}
