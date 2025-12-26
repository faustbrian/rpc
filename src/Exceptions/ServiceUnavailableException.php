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
 * Exception thrown when the service is temporarily unavailable.
 *
 * This exception represents a JSON-RPC server error that maps to HTTP 503,
 * indicating that the server is temporarily unable to handle requests due to
 * maintenance, overload, or other temporary conditions. Clients should retry
 * the request after a delay.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServiceUnavailableException extends AbstractRequestException
{
    /**
     * Create a new service unavailable exception instance.
     *
     * @param  null|string $detail Optional detailed explanation of why the service is
     *                             unavailable (e.g., "Database maintenance in progress").
     *                             If not provided, uses a default message explaining
     *                             temporary overload or maintenance. This detail is included
     *                             in the JSON:API error response.
     * @return self        The created exception instance with JSON-RPC error code -32000
     */
    public static function create(?string $detail = null): self
    {
        return self::new(-32_000, 'Server error', [
            [
                'status' => '503',
                'title' => 'Service Unavailable',
                'detail' => $detail ?? 'The server is currently unable to handle the request due to a temporary overload or scheduled maintenance, which will likely be alleviated after some delay.',
            ],
        ]);
    }

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTP 503 Service Unavailable status code
     */
    #[Override()]
    public function getStatusCode(): int
    {
        return 503;
    }
}
