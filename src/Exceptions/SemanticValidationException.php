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
 * Exception thrown when request fails semantic validation.
 *
 * Used for validation failures where the request structure is valid but the data
 * doesn't meet business rules or constraints. Provides a generic semantic validation
 * error with an optional detailed explanation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SemanticValidationException extends UnprocessableEntityException
{
    /**
     * Create a new semantic validation exception.
     *
     * @param  null|string $detail Optional detailed explanation of why the request
     *                             could not be processed (e.g., "Email format is invalid").
     *                             If not provided, uses a default message about semantic
     *                             errors. This detail is included in the JSON:API error response.
     * @return self        The created exception instance with JSON-RPC error code -32000
     */
    public static function create(?string $detail = null): self
    {
        return self::new(-32_000, 'Server error', [
            [
                'status' => '422',
                'title' => 'Unprocessable Entity',
                'detail' => $detail ?? 'The request was well-formed but was unable to be followed due to semantic errors.',
            ],
        ]);
    }

    /**
     * Create from Laravel ValidationException.
     *
     * @param  ValidationException $exception Laravel validation exception
     * @return self                The created exception instance
     */
    public static function createFromValidationException(ValidationException $exception): self
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
