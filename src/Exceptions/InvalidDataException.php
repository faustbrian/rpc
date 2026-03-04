<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Exception thrown when request data fails validation rules.
 *
 * Represents JSON-RPC error code -32602, specifically for validation failures in the
 * data payload of RPC requests. Transforms Laravel validation errors into JSON-RPC
 * compliant error responses with detailed field-level error information and JSON
 * Pointer references for precise error location tracking.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidDataException extends AbstractRequestException
{
    /**
     * Creates an invalid data exception from a Laravel validation exception.
     *
     * Transforms Laravel validation errors into a JSON-RPC compliant error response
     * format. Each validation error is converted into a separate error object with
     * JSON Pointer notation indicating the exact field location and HTTP 422 status.
     *
     * @param  ValidationException $exception Laravel validation exception containing field-level
     *                                        validation errors with attribute names and error
     *                                        messages. The errors are normalized into JSON-RPC
     *                                        error format with pointer references to specific
     *                                        fields in the request data payload.
     * @return self                a new instance containing all validation errors formatted as JSON-RPC
     *                             error objects, each with HTTP 422 status, JSON Pointer source location
     *                             (/params/data/{attribute}), and the specific validation message
     */
    public static function create(ValidationException $exception): self
    {
        $normalized = [];

        foreach ($exception->errors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $normalized[] = [
                    'status' => '422',
                    'source' => ['pointer' => '/params/data/'.$attribute],
                    'title' => 'Invalid params',
                    'detail' => $error,
                ];
            }
        }

        return self::new(-32_602, 'Invalid params', $normalized);
    }
}
