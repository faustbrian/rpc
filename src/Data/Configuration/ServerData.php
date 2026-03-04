<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data\Configuration;

use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Data\AbstractData;

/**
 * Configuration data for a single JSON-RPC server instance.
 *
 * Represents the configuration for one RPC server endpoint including its
 * routing information, version, middleware stack, available methods, and
 * OpenRPC schema definitions. Multiple server instances can be configured
 * to provide different API versions or isolated method sets.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServerData extends AbstractData
{
    /**
     * Create a new server configuration instance.
     *
     * @param string                                         $name                Unique identifier for this server instance, used
     *                                                                            to distinguish between multiple servers in the
     *                                                                            configuration (e.g., 'v1', 'admin', 'public').
     * @param string                                         $path                File system path or namespace where this server's
     *                                                                            method classes are located. Used for automatic
     *                                                                            method discovery during server initialization.
     * @param string                                         $route               HTTP route path where this server accepts requests.
     *                                                                            Should include leading slash and may include route
     *                                                                            parameters (e.g., '/api/v1/rpc', '/rpc/{version}').
     * @param string                                         $version             API version string for this server instance. Used
     *                                                                            for version tracking and documentation generation.
     *                                                                            Should follow semantic versioning (e.g., '1.0.0').
     * @param array<int, string>                             $middleware          Ordered array of middleware class names or aliases
     *                                                                            that apply to all requests to this server. Executed
     *                                                                            in array order before method dispatching occurs.
     * @param null|array<int, class-string<MethodInterface>> $methods             Optional array of method class names to register for
     *                                                                            this server. If null, all methods in the configured
     *                                                                            path will be auto-discovered and registered.
     * @param array<int, mixed>                              $content_descriptors reusable OpenRPC content descriptor definitions
     *                                                                            that can be referenced by multiple methods to
     *                                                                            reduce duplication in the API specification
     * @param array<int, mixed>                              $schemas             JSON Schema definitions for complex data types used
     *                                                                            by this server's methods. Used for validation and
     *                                                                            OpenRPC documentation generation.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $route,
        public readonly string $version,
        public readonly array $middleware,
        public readonly ?array $methods,
        public readonly array $content_descriptors,
        public readonly array $schemas,
    ) {}
}
