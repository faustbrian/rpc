<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Mixins;

use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Http\Controllers\MethodController;
use Cline\RPC\Repositories\ServerRepository;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

use function is_string;

/**
 * Provides Laravel Route macro for registering JSON-RPC servers.
 *
 * This mixin adds the `rpc()` macro to Laravel's Route facade, enabling convenient
 * registration of JSON-RPC server endpoints with automatic route configuration,
 * middleware application, and server repository registration.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RouteMixin
{
    /**
     * Returns a closure that registers a JSON-RPC server with Laravel routing.
     *
     * The returned closure can be used as a Route macro to register JSON-RPC servers
     * by creating a POST route with the server's configured path, name, and middleware.
     * The server instance is also registered in the ServerRepository for runtime access.
     *
     * Usage:
     * ```php
     * Route::rpc(MyJsonRpcServer::class);
     * ```
     *
     * @return Closure Closure that accepts a server class or instance and registers it
     */
    public function rpc(): Closure
    {
        /**
         * Registers a JSON-RPC server instance.
         *
         * @param class-string<ServerInterface>|ServerInterface $server Server class name or instance to register
         */
        return function (string|ServerInterface $server): void {
            if (is_string($server)) {
                /** @var ServerInterface $server */
                $server = App::make($server);
            }

            App::make(ServerRepository::class)->register($server);

            Route::post($server->getRoutePath(), MethodController::class)
                ->name($server->getRouteName())
                ->middleware($server->getMiddleware());
        };
    }
}
