<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Repositories;

use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Exceptions\ServerNotFoundException;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

use function is_string;

/**
 * Registry for JSON-RPC server instances.
 *
 * Manages the collection of registered JSON-RPC server endpoints, providing
 * registration and lookup capabilities by route path or route name. Servers
 * are indexed by their route path for efficient access during request handling.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServerRepository
{
    /**
     * Collection of registered server instances, indexed by route path.
     *
     * @var Collection<string, ServerInterface>
     */
    private Collection $servers;

    /**
     * Creates a new server repository with an empty collection.
     */
    public function __construct()
    {
        $this->servers = new Collection();
    }

    /**
     * Returns all registered server instances.
     *
     * @return Collection<string, ServerInterface> Collection of server instances
     */
    public function all(): Collection
    {
        return $this->servers;
    }

    /**
     * Finds a server by its route name.
     *
     * @param string $name Route name to search for (e.g., 'api.rpc')
     *
     * @throws ServerNotFoundException When no server matches the given route name
     *
     * @return ServerInterface Matching server instance
     */
    public function findByName(string $name): ServerInterface
    {
        return $this->findBy(fn (ServerInterface $server): bool => $server->getRouteName() === $name);
    }

    /**
     * Finds a server by its route path.
     *
     * @param string $path Route path to search for (e.g., '/api/rpc')
     *
     * @throws ServerNotFoundException When no server matches the given route path
     *
     * @return ServerInterface Matching server instance
     */
    public function findByPath(string $path): ServerInterface
    {
        return $this->findBy(fn (ServerInterface $server): bool => $server->getRoutePath() === $path);
    }

    /**
     * Registers a new server in the repository.
     *
     * Accepts either a server instance or a class name that will be resolved
     * from the container. Servers are indexed by their route path for fast lookup.
     *
     * @param ServerInterface|string $server Server class name or instance to register
     */
    public function register(string|ServerInterface $server): void
    {
        if (is_string($server)) {
            /** @var ServerInterface $server */
            $server = App::make($server);
        }

        $this->servers[$server->getRoutePath()] = $server;
    }

    /**
     * Finds a server by applying a custom closure predicate.
     *
     * @param Closure $closure Predicate to filter servers by
     *
     * @throws ServerNotFoundException When no server matches the predicate
     *
     * @return ServerInterface First matching server instance
     */
    private function findBy(Closure $closure): ServerInterface
    {
        $server = $this->servers->firstWhere(
            $closure,
            fn () => throw ServerNotFoundException::create(),
        );

        if ($server instanceof ServerInterface) {
            return $server;
        }

        throw ServerNotFoundException::create();
    }
}
