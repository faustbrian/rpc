<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Http\Middleware;

use Cline\RPC\Exceptions\ErrorRenderer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function throw_if;

/**
 * Middleware for rendering exceptions as JSON-RPC 2.0 error responses.
 *
 * This middleware intercepts uncaught exceptions in the request lifecycle and
 * automatically converts them to JSON-RPC 2.0 compliant error responses. It ensures
 * that all errors, whether from the RPC layer or underlying application, are formatted
 * consistently according to the JSON-RPC specification.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RenderThrowable
{
    /**
     * Handle an incoming request.
     *
     * Wraps the request in a try-catch block to intercept any thrown exceptions
     * and render them as JSON-RPC error responses.
     *
     * @param  Request                   $request HTTP request instance
     * @param  Closure(Request):Response $next    Next middleware in the chain
     * @return Response                  HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $throwable) {
            $response = ErrorRenderer::render($throwable, $request);

            throw_if(!$response instanceof JsonResponse, $throwable);

            return $response;
        }
    }
}
