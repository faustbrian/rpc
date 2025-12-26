<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Servers;

use Cline\OpenRpc\ContentDescriptor\CursorPaginatorContentDescriptor;
use Cline\OpenRpc\Schema\CursorPaginatorSchema;
use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Http\Middleware\BootServer;
use Cline\RPC\Http\Middleware\ForceJson;
use Cline\RPC\Methods\DiscoverMethod;
use Cline\RPC\Repositories\MethodRepository;
use Illuminate\Support\Facades\Config;
use Override;

/**
 * Base server class for JSON-RPC endpoints.
 *
 * Provides default configuration and initialization for JSON-RPC servers including
 * method registration, middleware setup, and OpenRPC schema definitions. Concrete
 * server classes extend this to define their specific RPC methods and configuration.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractServer implements ServerInterface
{
    /**
     * Repository managing all registered RPC methods for this server.
     */
    private readonly MethodRepository $methodRepository;

    /**
     * Initialize the server and register RPC methods.
     *
     * Creates a method repository with the server's defined methods and automatically
     * registers the system-level discovery method for OpenRPC introspection.
     */
    public function __construct()
    {
        $this->methodRepository = new MethodRepository($this->methods());
        $this->methodRepository->register(DiscoverMethod::class);
    }

    /**
     * Get the server name for identification and documentation.
     *
     * Returns the application name from Laravel configuration by default.
     * Override this method to provide a custom server name.
     *
     * @return string Server name from application configuration
     */
    #[Override()]
    public function getName(): string
    {
        return (string) Config::get('app.name');
    }

    /**
     * Get the URL path where this RPC server is mounted.
     *
     * Defines the route path for incoming JSON-RPC requests. Override this method
     * to customize the endpoint URL path.
     *
     * @return string URL path for the RPC endpoint (default: "/rpc")
     */
    #[Override()]
    public function getRoutePath(): string
    {
        return '/rpc';
    }

    /**
     * Get the Laravel route name for this RPC endpoint.
     *
     * Defines the named route identifier used in Laravel's routing system.
     * Override this method to customize the route name.
     *
     * @return string Laravel route name (default: "rpc")
     */
    #[Override()]
    public function getRouteName(): string
    {
        return 'rpc';
    }

    /**
     * Get the API version for this RPC server.
     *
     * Returns the semantic version string used in OpenRPC documentation and
     * API versioning. Override this method to specify a custom version.
     *
     * @return string Semantic version string (default: "1.0.0")
     */
    #[Override()]
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get the middleware stack applied to RPC requests.
     *
     * Returns an array of middleware class names that process requests before they
     * reach the RPC methods. Default middleware includes JSON content-type enforcement
     * and server bootstrapping. Override to customize the middleware stack.
     *
     * @return array<int, class-string> Array of middleware class names
     */
    #[Override()]
    public function getMiddleware(): array
    {
        return [
            ForceJson::class,
            BootServer::class,
        ];
    }

    /**
     * Get the method repository containing all registered RPC methods.
     *
     * Provides access to the server's method registry for request routing and
     * method invocation. The repository handles method lookup and execution.
     *
     * @return MethodRepository Repository managing registered RPC methods
     */
    #[Override()]
    public function getMethodRepository(): MethodRepository
    {
        return $this->methodRepository;
    }

    /**
     * Get the OpenRPC content descriptors for this server.
     *
     * Returns content descriptors that define reusable parameter and result schemas
     * in the OpenRPC specification. Default includes cursor pagination descriptor.
     * Override to add custom content descriptors.
     *
     * @return array<int, object> Array of OpenRPC content descriptor objects
     */
    #[Override()]
    public function getContentDescriptors(): array
    {
        return [
            CursorPaginatorContentDescriptor::create(),
        ];
    }

    /**
     * Get the OpenRPC schemas for this server.
     *
     * Returns schema definitions for complex data types used in the API.
     * Default includes cursor pagination schema. Override to add custom schemas
     * for your RPC methods' parameters and return types.
     *
     * @return array<int, object> Array of OpenRPC schema objects
     */
    #[Override()]
    public function getSchemas(): array
    {
        return [
            CursorPaginatorSchema::create(),
        ];
    }

    /**
     * Define the RPC methods available on this server.
     *
     * Returns an array of method class names that implement the MethodInterface.
     * Each method represents a callable RPC procedure that clients can invoke.
     *
     * @return array<int, class-string<MethodInterface>> Array of method class names
     */
    abstract public function methods(): array;
}
