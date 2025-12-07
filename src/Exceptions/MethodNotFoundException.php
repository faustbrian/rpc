<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Exception thrown when a requested RPC method does not exist or is not accessible.
 *
 * Represents JSON-RPC error code -32601 for requests to methods that are not
 * registered, not enabled, or do not exist in the RPC server's method registry.
 * This is distinct from Invalid Request (-32600) which indicates structural
 * problems, and Invalid Params (-32602) which indicates parameter issues.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodNotFoundException extends AbstractRequestException
{
    /**
     * Creates a method not found exception with optional error details.
     *
     * Generates a JSON-RPC compliant error response indicating that the requested
     * method is not available on this RPC server. This occurs when clients attempt
     * to invoke methods that don't exist, have been removed, or are disabled in
     * the current server configuration.
     *
     * @param  null|array<int, array<string, mixed>> $data Optional array of error details
     *                                                     following JSON:API error object
     *                                                     structure with status, source pointer,
     *                                                     title, and detail fields. Can include
     *                                                     suggestions for similar methods or
     *                                                     available method information.
     * @return self                                  a new instance with JSON-RPC error code -32601 (Method not found)
     *                                               and the provided error details, or null data for generic method
     *                                               not found responses without additional context
     */
    public static function create(?array $data = null): self
    {
        return self::new(-32_601, 'Method not found', $data);
    }
}
