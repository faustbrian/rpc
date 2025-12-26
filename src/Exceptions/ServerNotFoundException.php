<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Exception thrown when a requested JSON-RPC server cannot be found.
 *
 * This exception represents a JSON-RPC server error (-32099) that occurs when
 * attempting to route a request to a server that doesn't exist in the system's
 * server registry. This typically happens when the route name or server identifier
 * doesn't match any configured JSON-RPC server endpoints.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServerNotFoundException extends AbstractRequestException
{
    /**
     * Create a new server not found exception instance.
     *
     * @param  null|array<int|string, mixed> $data Additional error data to include in the JSON-RPC
     *                                             error response. This array is formatted according
     *                                             to JSON:API error object specifications and can
     *                                             contain information about the missing server identifier.
     * @return self                          The created exception instance with JSON-RPC error code -32099
     */
    public static function create(?array $data = null): self
    {
        return self::new(-32_099, 'Server not found', $data);
    }
}
