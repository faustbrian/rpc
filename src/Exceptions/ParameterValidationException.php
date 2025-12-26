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
 * Exception thrown when Laravel validation fails on request parameters.
 *
 * Converts Laravel's ValidationException into a JSON-RPC error response with
 * JSON:API formatted error objects. Each validation error is transformed into
 * a separate error object with a source pointer indicating the parameter field
 * that failed validation, enabling precise client-side error handling.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ParameterValidationException extends UnprocessableEntityException
{
    /**
     * Create an exception from a Laravel validation exception.
     *
     * Converts Laravel's ValidationException into a JSON-RPC error response with
     * JSON:API formatted error objects. Each validation error is transformed into
     * a separate error object with a source pointer indicating the parameter field
     * that failed validation, enabling precise client-side error handling.
     *
     * @param  ValidationException $exception The Laravel validation exception containing
     *                                        validation error messages organized by field name.
     *                                        Each field can have multiple error messages that
     *                                        are flattened into individual JSON:API error objects.
     * @return self                The created exception instance with normalized validation errors
     */
    public static function fromValidationException(ValidationException $exception): self
    {
        $normalized = [];

        foreach ($exception->errors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $normalized[] = [
                    'status' => '422',
                    'source' => ['pointer' => '/params/'.$attribute],
                    'title' => 'Invalid params',
                    'detail' => $error,
                ];
            }
        }

        return self::new(-32_000, 'Unprocessable Entity', $normalized);
    }
}
