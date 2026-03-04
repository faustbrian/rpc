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
 * Exception thrown when rate limiting is triggered.
 *
 * This exception represents a JSON-RPC server error that maps to HTTP 429,
 * indicating that the client has sent too many requests in a given time period
 * and has exceeded the rate limit. Clients should implement exponential backoff
 * and retry the request after a delay.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TooManyRequestsException extends AbstractRequestException
{
    /**
     * Create a new too many requests exception instance.
     *
     * @param  null|string $detail Optional detailed explanation of the rate limit
     *                             violation (e.g., "Rate limit: 100 requests per minute").
     *                             If not provided, uses a default message explaining that
     *                             the rate limit has been exceeded. This detail is included
     *                             in the JSON:API error response.
     * @return self        The created exception instance with JSON-RPC error code -32000
     */
    public static function create(?string $detail = null): self
    {
        return self::new(-32_000, 'Server error', [
            [
                'status' => '429',
                'title' => 'Too Many Requests',
                'detail' => $detail ?? 'The server is refusing to service the request because the rate limit has been exceeded. Please wait and try again later.',
            ],
        ]);
    }

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTP 429 Too Many Requests status code
     */
    #[Override()]
    public function getStatusCode(): int
    {
        return 429;
    }
}
