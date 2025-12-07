<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Illuminate\Validation\Validator;

/**
 * Exception thrown when Laravel validator detects structural request errors.
 *
 * Transforms Laravel validation errors for JSON-RPC request structure into a
 * JSON-RPC compliant error response. Each validation error is converted into
 * an error object with JSON Pointer notation indicating the exact location of
 * the structural violation in the request document.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RequestValidationFailedException extends InvalidRequestException
{
    /**
     * Creates an invalid request exception from a Laravel validator instance.
     *
     * Transforms Laravel validation errors for JSON-RPC request structure into a
     * JSON-RPC compliant error response. Each validation error is converted into
     * an error object with JSON Pointer notation indicating the exact location of
     * the structural violation in the request document.
     *
     * @param  Validator $validator Laravel validator instance containing validation errors
     *                              for the JSON-RPC request structure. Errors are typically
     *                              for missing or invalid top-level request members like
     *                              'jsonrpc', 'method', 'id', or 'params' rather than the
     *                              method parameter validation handled by other exceptions.
     * @return self      a new instance containing all validation errors formatted as JSON-RPC
     *                   error objects, each with HTTP 422 status, JSON Pointer source location
     *                   (/{attribute}), and the specific validation failure message
     */
    public static function fromValidator(Validator $validator): self
    {
        $normalized = [];

        foreach ($validator->errors()->messages() as $attribute => $errors) {
            foreach ($errors as $error) {
                $normalized[] = [
                    'status' => '422',
                    'source' => ['pointer' => '/'.$attribute],
                    'title' => 'Invalid member',
                    'detail' => $error,
                ];
            }
        }

        return self::new(-32_600, 'Invalid Request', $normalized);
    }
}
