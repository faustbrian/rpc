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
 * Exception thrown when authentication is required but not provided or invalid.
 *
 * This exception represents a JSON-RPC server error that maps to HTTP 401,
 * indicating that the request requires user authentication. This occurs when
 * authentication credentials are missing, invalid, or expired. The client must
 * provide valid credentials and retry the request.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnauthorizedException extends AbstractRequestException
{
    /**
     * Create a new unauthorized exception instance.
     *
     * @param  null|string $detail Optional detailed explanation of the authentication
     *                             failure (e.g., "Invalid API token provided"). If not
     *                             provided, uses a default message indicating the user
     *                             is not authorized. This detail is included in the
     *                             JSON:API error response.
     * @return self        The created exception instance with JSON-RPC error code -32000
     */
    public static function create(?string $detail = null): self
    {
        return self::new(-32_000, 'Server error', [
            [
                'status' => '401',
                'title' => 'Unauthorized',
                'detail' => $detail ?? 'You are not authorized to perform this action.',
            ],
        ]);
    }

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTP 401 Unauthorized status code
     */
    #[Override()]
    public function getStatusCode(): int
    {
        return 401;
    }
}
