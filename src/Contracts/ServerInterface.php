<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Contracts;

use Cline\RPC\Repositories\MethodRepository;

/**
 * Contract for JSON-RPC server configuration and metadata.
 *
 * Defines the interface for server instances that manage JSON-RPC endpoints,
 * including route configuration, method registration, middleware stacks, and
 * OpenRPC schema definitions. Each server represents a distinct RPC endpoint
 * with its own configuration, version, and set of available methods.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface ServerInterface
{
    /**
     * Get the unique server name identifier.
     *
     * Used to distinguish between multiple server instances in multi-server
     * configurations. Should be a unique, descriptive name for the server.
     *
     * @return string Server name identifier
     */
    public function getName(): string;

    /**
     * Get the HTTP route path for this server endpoint.
     *
     * Returns the URL path where this server accepts JSON-RPC requests.
     * Should include leading slash (e.g., "/api/v1/rpc", "/rpc").
     *
     * @return string HTTP route path
     */
    public function getRoutePath(): string;

    /**
     * Get the Laravel route name for this server endpoint.
     *
     * Returns the named route identifier used for URL generation and
     * route resolution within Laravel's routing system.
     *
     * @return string Laravel route name
     */
    public function getRouteName(): string;

    /**
     * Get the API version identifier for this server.
     *
     * Version string used for API versioning and backwards compatibility
     * tracking. Should follow semantic versioning (e.g., "1.0.0", "2.1.0").
     *
     * @return string API version string
     */
    public function getVersion(): string;

    /**
     * Get the middleware stack for this server endpoint.
     *
     * Returns an array of middleware class names or aliases that should be
     * applied to all requests to this server, in the order they should execute.
     *
     * @return array<int, string> Ordered array of middleware names
     */
    public function getMiddleware(): array;

    /**
     * Get the method repository containing all registered RPC methods.
     *
     * Returns the repository instance that manages method discovery,
     * registration, and dispatching for this server's available methods.
     *
     * @return MethodRepository Method repository instance
     */
    public function getMethodRepository(): MethodRepository;

    /**
     * Get reusable content descriptor definitions for this server.
     *
     * Returns an array of content descriptor objects that can be referenced
     * by multiple methods to reduce duplication in OpenRPC schemas.
     *
     * @return array<int, mixed> Array of reusable content descriptors
     */
    public function getContentDescriptors(): array;

    /**
     * Get JSON Schema definitions for this server.
     *
     * Returns an array of schema objects defining the structure of complex
     * data types used by this server's methods. Used for validation and
     * OpenRPC documentation generation.
     *
     * @return array<int, mixed> Array of JSON Schema definitions
     */
    public function getSchemas(): array;
}
