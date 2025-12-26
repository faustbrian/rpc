<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Servers;

use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Data\Configuration\ServerData;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Override;

use function class_exists;
use function class_implements;
use function collect;
use function in_array;
use function mb_rtrim;
use function str_replace;

/**
 * Configuration-driven JSON-RPC server implementation.
 *
 * Creates RPC servers dynamically from configuration data instead of requiring
 * concrete server classes. Automatically discovers and registers methods from
 * configured directories or explicit method lists, enabling flexible server
 * deployment through configuration files.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ConfigurationServer extends AbstractServer
{
    /**
     * Create a new configuration-based server instance.
     *
     * @param ServerData $server Configuration data defining the server's name, routes,
     *                           middleware, methods, and OpenRPC schema components.
     *                           This data-driven approach enables creating multiple
     *                           RPC endpoints without additional code.
     */
    public function __construct(
        private readonly ServerData $server,
        #[Config('rpc.paths.methods', '')]
        private readonly string $methodsPath,
        #[Config('rpc.namespaces.methods', '')]
        private readonly string $methodsNamespace,
    ) {
        parent::__construct();
    }

    /**
     * Get the server name from configuration.
     *
     * @return string Server name defined in the configuration data
     */
    #[Override()]
    public function getName(): string
    {
        return $this->server->name;
    }

    /**
     * Get the URL path for this server from configuration.
     *
     * @return string URL path where the RPC endpoint is mounted
     */
    #[Override()]
    public function getRoutePath(): string
    {
        return $this->server->path;
    }

    /**
     * Get the Laravel route name from configuration.
     *
     * @return string Named route identifier for Laravel's routing system
     */
    #[Override()]
    public function getRouteName(): string
    {
        return $this->server->route;
    }

    /**
     * Get the API version from configuration.
     *
     * @return string Semantic version string for this server instance
     */
    #[Override()]
    public function getVersion(): string
    {
        return $this->server->version;
    }

    /**
     * Get the middleware stack from configuration.
     *
     * @return array<int, class-string> Array of middleware class names to apply
     */
    #[Override()]
    public function getMiddleware(): array
    {
        return $this->server->middleware;
    }

    /**
     * Get the RPC methods for this server.
     *
     * Returns methods from configuration if explicitly defined, otherwise scans
     * the configured methods directory to auto-discover method classes. The
     * discovery process filters for classes implementing MethodInterface and
     * excludes abstract classes and test files.
     *
     * @return array<int, class-string<MethodInterface>> Array of method class names
     */
    #[Override()]
    public function methods(): array
    {
        $methods = $this->server->methods;

        if ($methods === null) {
            $methodsPath = mb_rtrim($this->methodsPath, '/');

            if (!File::isDirectory($methodsPath)) {
                return [];
            }

            $methodsNamespace = $this->methodsNamespace;

            /** @var array<int, class-string<MethodInterface>> */
            $discovered = collect(File::allFiles($methodsPath))
                ->map(fn ($file): string => $file->getPathname())
                ->filter(fn ($file): bool => Str::endsWith($file, ['.php']))
                ->map(fn ($file): string => str_replace($methodsPath.'/', $methodsNamespace.'\\', $file))
                ->map(fn ($file): string => str_replace('.php', '', $file))
                ->map(fn ($file): string => str_replace('/', '\\', $file))
                ->reject(fn ($file): bool => Str::contains($file, ['AbstractMethod', 'Test.php']))
                ->filter(function (string $class): bool {
                    if (!class_exists($class)) {
                        return false;
                    }

                    return in_array(MethodInterface::class, (array) class_implements($class), true);
                })
                ->values()
                ->all();

            return $discovered;
        }

        return $methods;
    }

    /**
     * Get the OpenRPC content descriptors from configuration.
     *
     * Returns the configured content descriptors that define reusable parameter
     * and result schemas for this server's OpenRPC documentation.
     *
     * @return array<int, object> Array of OpenRPC content descriptor objects
     */
    #[Override()]
    public function getContentDescriptors(): array
    {
        return $this->server->content_descriptors;
    }

    /**
     * Get the OpenRPC schemas from configuration.
     *
     * Returns the configured schema definitions for complex data types used
     * in this server's RPC methods and their documentation.
     *
     * @return array<int, object> Array of OpenRPC schema objects
     */
    #[Override()]
    public function getSchemas(): array
    {
        return $this->server->schemas;
    }
}
