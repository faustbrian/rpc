<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

use function array_filter;
use function response;

/**
 * Renders exceptions as JSON-RPC 2.0 error responses.
 *
 * This class provides the core logic for converting exceptions into JSON-RPC 2.0
 * compliant error responses. It is shared between the middleware and the exception
 * handler trait/action to ensure consistent error formatting across the application.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ErrorRenderer
{
    /**
     * Render an exception as a JSON-RPC error response.
     *
     * Converts the exception to a JSON-RPC exception via ExceptionMapper, then formats
     * it as a JSON-RPC 2.0 error response with appropriate HTTP status code and headers.
     * Only renders if the request expects JSON responses (wantsJson).
     *
     * @param  Throwable         $exception The exception to render
     * @param  Request           $request   The HTTP request
     * @return null|JsonResponse JSON-RPC error response or null if request doesn't want JSON
     */
    public static function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (!$request->wantsJson()) {
            return null;
        }

        $exception = ExceptionMapper::execute($exception);

        /** @var JsonResponse */
        return response()->json(
            array_filter([
                'jsonrpc' => '2.0',
                'id' => $request->input('id'),
                'error' => $exception->toArray(),
            ]),
            $exception->getStatusCode(),
            $exception->getHeaders(),
        );
    }
}
