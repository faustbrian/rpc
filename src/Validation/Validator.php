<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Validation;

use Illuminate\Validation\ValidationException;

use function throw_if;
use function validator;

/**
 * Validation utility for RPC request data.
 *
 * Provides a simplified interface for validating request parameters using Laravel's
 * validation system. Throws validation exceptions on failure for centralized error
 * handling in the JSON-RPC request pipeline.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Validator
{
    /**
     * Validate data against the given validation rules.
     *
     * Creates a Laravel validator instance and validates the provided data against
     * the specified rules. Throws a ValidationException with all error messages if
     * validation fails, which is then caught and formatted by the RPC error handler.
     *
     * @param array<string, mixed>                     $data  Data to validate, typically RPC request parameters
     * @param array<string, array<int, string>|string> $rules Laravel validation rules to apply to the data
     *
     * @throws ValidationException When validation fails, containing all validation error messages
     */
    public static function validate(array $data, array $rules): void
    {
        $validator = validator($data, $rules);

        throw_if($validator->fails(), ValidationException::withMessages($validator->errors()->all()));
    }
}
