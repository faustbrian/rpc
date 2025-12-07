<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Http\Middleware;

use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Exceptions\RouteNameRequiredException;
use Cline\RPC\Repositories\ServerRepository;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

use function throw_if;

/**
 * Middleware that bootstraps the JSON-RPC server for the current request.
 *
 * Resolves the appropriate server instance based on the route name and binds it
 * to the service container. This enables request-specific server configuration
 * and ensures the correct server handles each JSON-RPC request.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class BootServer
{
    /**
     * Create a new server bootstrapping middleware instance.
     *
     * @param Container        $container        laravel service container instance used for binding
     *                                           the resolved server instance and managing its lifecycle
     *                                           throughout the request
     * @param ServerRepository $serverRepository repository for retrieving server configurations
     *                                           by name, enabling route-specific server resolution
     *                                           and configuration loading
     */
    public function __construct(
        private Container $container,
        private ServerRepository $serverRepository,
    ) {}

    /**
     * Handle the incoming JSON-RPC request and bootstrap the appropriate server.
     *
     * Retrieves the route name from the request, resolves the corresponding server
     * configuration, and binds it to the container for use in downstream handlers.
     *
     * @param Request $request The incoming HTTP request containing the route information
     * @param Closure $next    The next middleware in the pipeline
     *
     * @throws RouteNameRequiredException When the route name is missing, preventing server resolution
     *
     * @return Response The response from the next middleware or handler
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        throw_if($routeName === null, RouteNameRequiredException::create());

        $this->container->instance(
            ServerInterface::class,
            $this->serverRepository->findByName($routeName),
        );

        return $next($request);
    }

    /**
     * Clean up the server instance after the response has been sent.
     *
     * Removes the server instance from the container to prevent memory leaks
     * and ensure fresh server resolution for subsequent requests. Skipped during
     * unit tests to maintain test isolation and prevent container state issues.
     */
    public function terminate(): void
    {
        if (App::runningUnitTests()) {
            return;
        }

        $this->container->forgetInstance(ServerInterface::class);
    }
}
