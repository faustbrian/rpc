<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Facades;

use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Repositories\MethodRepository;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * Facade for accessing the JSON-RPC server instance.
 *
 * This facade provides static access to the current JSON-RPC server's configuration,
 * metadata, and method repository. It proxies calls to the active ServerInterface
 * implementation bound in the service container. Use this facade to access server
 * properties like OpenRPC schemas, middleware, and routing information within your
 * JSON-RPC method implementations.
 *
 * @method static array<int, mixed>  getContentDescriptors()
 * @method static MethodRepository   getMethodRepository()
 * @method static array<int, string> getMiddleware()
 * @method static string             getName()
 * @method static string             getRouteName()
 * @method static string             getRoutePath()
 * @method static array<int, mixed>  getSchemas()
 * @method static string             getVersion()
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see ServerInterface
 */
final class Server extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The service container binding key for the server interface
     */
    #[Override()]
    protected static function getFacadeAccessor(): string
    {
        return ServerInterface::class;
    }
}
