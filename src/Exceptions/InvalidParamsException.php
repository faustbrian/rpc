<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Exception thrown when method parameters are invalid or malformed.
 *
 * Represents JSON-RPC error code -32602 for general parameter validation failures.
 * This is a generic parameter exception used when request parameters do not meet
 * method requirements but are not covered by more specific validation exceptions
 * like InvalidDataException or InvalidFieldsException.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidParamsException extends AbstractRequestException
{
    /**
     * Creates an invalid params exception with optional error details.
     *
     * Generates a JSON-RPC compliant error response for parameter validation failures.
     * This method is typically used for general parameter errors that don't require
     * the detailed field-level error tracking provided by more specific exceptions.
     *
     * @param  null|array<int, array<string, mixed>> $data Optional array of error details
     *                                                     following JSON:API error object
     *                                                     structure. Each error should contain
     *                                                     status, source pointer, title, and
     *                                                     detail fields for client debugging.
     * @return self                                  a new instance with JSON-RPC error code -32602 (Invalid params)
     *                                               and the provided error details, or null data for generic parameter
     *                                               validation failures without specific field information
     */
    public static function create(?array $data = null): self
    {
        return self::new(-32_602, 'Invalid params', $data);
    }
}
