<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions\Concerns;

use Cline\RPC\Exceptions\ErrorRenderer;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Trait for adding JSON-RPC error rendering to exception handlers.
 *
 * This trait provides a convenient method to configure JSON-RPC 2.0 compliant
 * error rendering in Laravel exception handlers. Designed to be mixed into
 * classes that extend Laravel's Exceptions configuration for easy integration
 * of JSON-RPC error handling.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @mixin Exceptions
 */
trait RendersThrowable
{
    /**
     * Register JSON-RPC exception rendering.
     *
     * Configures the exception handler to intercept and render exceptions as
     * JSON-RPC 2.0 error responses for JSON requests. Maps Laravel exceptions
     * to JSON-RPC exception types and formats them with appropriate error codes,
     * messages, and HTTP status codes.
     */
    protected function renderableThrowable(): void
    {
        $this->renderable(
            fn (Throwable $exception, Request $request): ?JsonResponse => ErrorRenderer::render($exception, $request),
        );
    }
}
