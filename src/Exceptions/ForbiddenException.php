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
 * Exception for forbidden access errors (HTTP 403).
 *
 * This exception represents authorization failures where the client is authenticated
 * but lacks permission to access the requested resource or perform the requested action.
 * Maps to HTTP 403 Forbidden status and JSON-RPC server error code -32000.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ForbiddenException extends AbstractRequestException
{
    /**
     * Create a new forbidden exception instance.
     *
     * Factory method that constructs a forbidden exception with a standardized error
     * structure containing status code, title, and detail message. Used when a user
     * is authenticated but lacks the necessary permissions for the requested operation.
     *
     * @param  null|string $detail Optional detailed error message explaining why access
     *                             was denied. Defaults to a generic authorization failure
     *                             message if not provided.
     * @return self        the constructed forbidden exception instance
     */
    public static function create(?string $detail = null): self
    {
        return self::new(-32_000, 'Server error', [
            [
                'status' => '403',
                'title' => 'Forbidden',
                'detail' => $detail ?? 'You are not authorized to perform this action.',
            ],
        ]);
    }

    /**
     * Get the HTTP status code for this exception.
     *
     * @return int always returns 403 (Forbidden) to indicate authorization failure
     */
    #[Override()]
    public function getStatusCode(): int
    {
        return 403;
    }
}
