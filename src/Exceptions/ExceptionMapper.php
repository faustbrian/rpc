<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Maps Laravel exceptions to JSON-RPC exception types.
 *
 * This class provides centralized exception mapping logic that transforms standard
 * Laravel and PHP exceptions into appropriate JSON-RPC exception instances with
 * proper error codes and messages. Ensures consistent error handling across the
 * JSON-RPC server implementation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ExceptionMapper
{
    /**
     * Map an exception to a JSON-RPC exception.
     *
     * Transforms standard Laravel and PHP exceptions into JSON-RPC compliant exception
     * instances with appropriate error codes and messages. The mapping follows these rules:
     * - AbstractRequestException: passed through unchanged (already JSON-RPC compliant)
     * - AuthenticationException: mapped to UnauthorizedException (401)
     * - AuthorizationException: mapped to ForbiddenException (403)
     * - ItemNotFoundException/ModelNotFoundException: mapped to ResourceNotFoundException (404)
     * - ThrottleRequestsException: mapped to TooManyRequestsException (429)
     * - ValidationException: mapped to UnprocessableEntityException with validation details (422)
     * - All other exceptions: mapped to InternalErrorException (500)
     *
     * @param  Throwable                $exception the exception to map to a JSON-RPC exception type
     * @return AbstractRequestException the mapped JSON-RPC exception with appropriate error code and message
     */
    public static function execute(Throwable $exception): AbstractRequestException
    {
        return match (true) {
            $exception instanceof AbstractRequestException => $exception,
            $exception instanceof AuthenticationException => UnauthorizedException::create(),
            $exception instanceof AuthorizationException => ForbiddenException::create(),
            $exception instanceof ItemNotFoundException => ResourceNotFoundException::create(),
            $exception instanceof ModelNotFoundException => ResourceNotFoundException::create(),
            $exception instanceof ThrottleRequestsException => TooManyRequestsException::create(),
            $exception instanceof ValidationException => ParameterValidationException::fromValidationException($exception),
            default => InternalErrorException::create($exception),
        };
    }
}
