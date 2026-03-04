<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Exception thrown when a JSON-RPC request fails structural validation.
 *
 * This exception represents requests that don't meet basic JSON-RPC specification
 * requirements, such as missing required members or invalid structure. Used for
 * generic structural failures with optional detailed error information.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StructurallyInvalidRequestException extends InvalidRequestException
{
    /**
     * Creates an invalid request exception with optional error details.
     *
     * Generates a JSON-RPC compliant error response for structural request validation
     * failures. Used for requests that don't meet basic JSON-RPC specification
     * requirements rather than method-specific parameter validation issues.
     *
     * @param  null|array<int, array<string, mixed>> $data optional array of error details
     *                                                     following JSON:API error object
     *                                                     structure with status, source pointer,
     *                                                     title, and detail fields for describing
     *                                                     specific structural violations
     * @return self                                  a new instance with JSON-RPC error code -32600 (Invalid Request)
     *                                               and the provided error details, or null data for generic request
     *                                               structure validation failures
     */
    public static function create(?array $data = null): self
    {
        return self::new(-32_600, 'Invalid Request', $data);
    }
}
