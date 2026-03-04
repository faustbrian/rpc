<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Exception thrown when a generic server error occurs.
 *
 * This exception represents a JSON-RPC server error (-32000), which is the
 * standard error code for server-side errors that don't fit into more specific
 * categories. Use this exception for unexpected server failures, infrastructure
 * issues, or other internal errors that prevent request processing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServerErrorException extends AbstractRequestException
{
    /**
     * Create a new server error exception instance.
     *
     * @param  null|array<int|string, mixed> $data Additional error data to include in the JSON-RPC
     *                                             error response. This array is formatted according
     *                                             to JSON:API error object specifications and can
     *                                             contain detailed information about the server failure.
     * @return self                          The created exception instance with JSON-RPC error code -32000
     */
    public static function create(?array $data = null): self
    {
        return self::new(-32_000, 'Server error', $data);
    }
}
