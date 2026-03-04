<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC;

use Cline\RPC\Contracts\ProtocolInterface;
use Cline\RPC\Contracts\SerializerInterface;
use Cline\RPC\Data\Configuration\ConfigurationData;
use Cline\RPC\Mixins\RouteMixin;
use Cline\RPC\Repositories\ResourceRepository;
use Cline\RPC\Repositories\ServerRepository;
use Cline\RPC\Requests\RequestHandler;
use Cline\RPC\Servers\ConfigurationServer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Override;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;

use function assert;
use function class_exists;
use function config;
use function is_string;

/**
 * Laravel service provider for the JSON-RPC package.
 *
 * Handles package registration, configuration publishing, route registration,
 * and resource discovery. Automatically configures RPC servers based on the
 * published configuration file.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package's publishable assets and install commands.
     *
     * Defines the package name, configuration files to publish, and the installation
     * command that publishes configuration and migration files to the Laravel application.
     *
     * @param Package $package Package configuration instance
     */
    #[Override()]
    public function configurePackage(Package $package): void
    {
        $package
            ->name('json-rpc')
            ->hasConfigFile(['json-rpc', 'rpc'])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->publishConfigFile();
                $command->publishMigrations();
            });
    }

    /**
     * Register package services in the Laravel container.
     *
     * Binds the ProtocolInterface, ServerRepository, and RequestHandler as
     * singletons to ensure consistent protocol handling, server configuration, and
     * request handling throughout the application lifecycle. The protocol is
     * instantiated based on the configuration file setting.
     */
    #[Override()]
    public function packageRegistered(): void
    {
        // Bind ProtocolInterface
        $this->app->singleton(function (): ProtocolInterface {
            $protocolClass = config('rpc.protocol', config('rpc.serializer')); // Fallback for BC
            assert(is_string($protocolClass));
            assert(class_exists($protocolClass));

            $protocol = new $protocolClass();
            assert($protocol instanceof ProtocolInterface);

            return $protocol;
        });

        // Bind legacy SerializerInterface to same instance for BC
        $this->app->singleton(function (Application $app): SerializerInterface {
            $protocol = $app->make(ProtocolInterface::class);
            assert($protocol instanceof SerializerInterface);

            return $protocol;
        });

        $this->app->singleton(ServerRepository::class);

        $this->app->singleton(function (Application $app): RequestHandler {
            $protocol = $app->make(ProtocolInterface::class);
            assert($protocol instanceof ProtocolInterface);

            return new RequestHandler($protocol);
        });
    }

    /**
     * Perform operations during package booting phase.
     *
     * Registers the custom Route mixin that adds the rpc() method to Laravel's
     * route facade, enabling convenient RPC server registration in route files.
     */
    #[Override()]
    public function bootingPackage(): void
    {
        Route::mixin(
            new RouteMixin(),
        );
    }

    /**
     * Boot package services after all providers are registered.
     *
     * Loads the RPC configuration, registers resource mappings, and creates
     * RPC server routes based on the configuration. Gracefully handles missing
     * or invalid configuration in console environments to prevent installation
     * errors before configuration is published.
     *
     * @throws Throwable Configuration validation errors in non-console environments
     */
    #[Override()]
    public function packageBooted(): void
    {
        try {
            $configuration = ConfigurationData::validateAndCreate((array) config('rpc'));

            foreach ($configuration->resources as $model => $resource) {
                ResourceRepository::register($model, $resource);
            }

            foreach ($configuration->servers as $server) {
                $methodsPath = config('rpc.paths.methods', '');
                $methodsNamespace = config('rpc.namespaces.methods', '');

                // @phpstan-ignore-next-line
                Route::rpc(
                    new ConfigurationServer(
                        $server,
                        is_string($methodsPath) ? $methodsPath : '',
                        is_string($methodsNamespace) ? $methodsNamespace : '',
                    ),
                );
            }
        } catch (Throwable $throwable) {
            if (App::runningInConsole()) {
                return;
            }

            throw $throwable;
        }
    }
}
