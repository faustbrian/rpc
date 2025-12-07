<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Exception thrown when route name is missing during server bootstrapping.
 *
 * Thrown when the BootServer middleware cannot resolve the server instance
 * because the route lacks a name identifier required for server lookup.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RouteNameRequiredException extends BadRequestHttpException implements RpcException
{
    /**
     * Create exception for missing route name.
     *
     * @return self The created exception instance
     */
    public static function create(): self
    {
        return new self('A route name is required to boot the server.');
    }
}
