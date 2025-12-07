# Servers

Create and configure JSON-RPC servers with custom routes, middleware, and OpenRPC schemas.

## Creating a Server

### Basic Server

Extend `AbstractServer` to create a custom RPC server:

```php
<?php

namespace App\RPC\Servers;

use App\RPC\Methods\UserListMethod;
use App\RPC\Methods\UserGetMethod;
use Cline\RPC\Servers\AbstractServer;
use Override;

class UserServer extends AbstractServer
{
    #[Override()]
    public function getName(): string
    {
        return 'User API Server';
    }

    #[Override()]
    public function getRoutePath(): string
    {
        return '/api/users';
    }

    #[Override()]
    public function getRouteName(): string
    {
        return 'api.users.rpc';
    }

    #[Override()]
    public function getVersion(): string
    {
        return '2.0.0';
    }

    #[Override()]
    public function methods(): array
    {
        return [
            UserListMethod::class,
            UserGetMethod::class,
        ];
    }
}
```

### Server Configuration

The `AbstractServer` provides several configuration methods:

#### getName()

Returns the server name for identification in OpenRPC documentation:

```php
#[Override()]
public function getName(): string
{
    return 'My API Server';
}
```

Default: Returns `app.name` from Laravel configuration.

#### getRoutePath()

Defines the URL path where the RPC server is mounted:

```php
#[Override()]
public function getRoutePath(): string
{
    return '/api/v2/rpc';
}
```

Default: `/rpc`

#### getRouteName()

Defines the Laravel route name:

```php
#[Override()]
public function getRouteName(): string
{
    return 'api.v2.rpc';
}
```

Default: `rpc`

#### getVersion()

Specifies the API version for OpenRPC documentation:

```php
#[Override()]
public function getVersion(): string
{
    return '2.1.0';
}
```

Default: `1.0.0`

## Middleware

### Adding Custom Middleware

Override `getMiddleware()` to add middleware to your RPC endpoint:

```php
use App\Http\Middleware\AuthenticateRpc;
use App\Http\Middleware\RateLimitRpc;
use Cline\RPC\Http\Middleware\BootServer;
use Cline\RPC\Http\Middleware\ForceJson;

#[Override()]
public function getMiddleware(): array
{
    return [
        ForceJson::class,
        BootServer::class,
        AuthenticateRpc::class,
        RateLimitRpc::class,
    ];
}
```

Default middleware:
- `ForceJson` - Ensures JSON content-type headers
- `BootServer` - Initializes the RPC server context

### Creating RPC Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateRpc
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-RPC-Token');

        if (!$this->isValidToken($token)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Invalid authentication token',
                ],
                'id' => null,
            ], 401);
        }

        return $next($request);
    }

    private function isValidToken(?string $token): bool
    {
        // Implement token validation
        return $token === config('rpc.api_token');
    }
}
```

## Configuration-Based Servers

Instead of creating server classes, define servers in `config/rpc.php`:

```php
return [
    'configuration' => [
        'servers' => [
            [
                'name' => 'User API Server',
                'path' => '/api/users',
                'route' => 'api.users.rpc',
                'version' => '2.0.0',
                'middleware' => [
                    \App\Http\Middleware\AuthenticateRpc::class,
                ],
                'methods' => [
                    \App\RPC\Methods\UserListMethod::class,
                    \App\RPC\Methods\UserGetMethod::class,
                ],
                'content_descriptors' => [],
                'schemas' => [],
            ],
        ],
    ],
];
```

### Auto-Discovery of Methods

Set `methods` to `null` to auto-discover methods from a directory:

```php
'configuration' => [
    'servers' => [
        [
            'name' => 'User API Server',
            'path' => '/api/users',
            'route' => 'api.users.rpc',
            'version' => '2.0.0',
            'methods' => null, // Auto-discover
        ],
    ],
],
'paths' => [
    'methods' => app_path('RPC/Methods/Users'),
],
'namespaces' => [
    'methods' => 'App\\RPC\\Methods\\Users',
],
```

The server will automatically discover and register all classes in the directory that implement `MethodInterface`.

## OpenRPC Schema

### Content Descriptors

Define reusable parameter and result schemas:

```php
use Cline\OpenRpc\ContentDescriptor\ContentDescriptor;
use Cline\OpenRpc\Schema\ObjectSchema;

#[Override()]
public function getContentDescriptors(): array
{
    return [
        ContentDescriptor::create()
            ->name('User')
            ->schema(
                ObjectSchema::create()
                    ->property('id', 'integer')
                    ->property('name', 'string')
                    ->property('email', 'string')
                    ->property('created_at', 'string')
                    ->required(['id', 'name', 'email'])
            ),
    ];
}
```

### Custom Schemas

Add complex type definitions for OpenRPC documentation:

```php
use Cline\OpenRpc\Schema\ObjectSchema;
use Cline\OpenRpc\Schema\ArraySchema;

#[Override()]
public function getSchemas(): array
{
    return [
        ObjectSchema::create()
            ->id('UserList')
            ->property('users', ArraySchema::create()->items('User'))
            ->property('total', 'integer')
            ->required(['users', 'total']),
    ];
}
```

### Using Content Descriptors in Methods

Reference descriptors by name in your methods:

```php
use Cline\OpenRpc\ValueObject\ContentDescriptorValue;

#[Override()]
public function getResult(): ?ContentDescriptorValue
{
    return ContentDescriptorValue::create()
        ->name('result')
        ->schema(['$ref' => '#/components/contentDescriptors/User']);
}
```

## Multiple Servers

Run multiple RPC servers on different endpoints:

### Class-Based Servers

```php
// config/rpc.php
return [
    'servers' => [
        \App\RPC\Servers\UserServer::class,
        \App\RPC\Servers\PaymentServer::class,
        \App\RPC\Servers\AdminServer::class,
    ],
];
```

Each server mounts at its own path:
- `/api/users` - UserServer
- `/api/payments` - PaymentServer
- `/admin/rpc` - AdminServer

### Configuration-Based Servers

```php
'configuration' => [
    'servers' => [
        [
            'name' => 'Public API',
            'path' => '/api/v1/rpc',
            'route' => 'api.v1.rpc',
            'methods' => [...],
        ],
        [
            'name' => 'Admin API',
            'path' => '/admin/rpc',
            'route' => 'admin.rpc',
            'middleware' => [AdminAuth::class],
            'methods' => [...],
        ],
    ],
],
```

## Server Registration

### Publish Configuration

```bash
php artisan vendor:publish --provider="Cline\RPC\ServiceProvider"
```

### Register Servers

Edit `config/rpc.php` to register your servers:

```php
return [
    'servers' => [
        App\RPC\Servers\ApiServer::class,
    ],
];
```

Routes are automatically registered when the application boots.

## Practical Examples

### Versioned API Server

```php
class ApiV2Server extends AbstractServer
{
    #[Override()]
    public function getRoutePath(): string
    {
        return '/api/v2/rpc';
    }

    #[Override()]
    public function getVersion(): string
    {
        return '2.0.0';
    }

    #[Override()]
    public function methods(): array
    {
        return [
            V2\UserListMethod::class,
            V2\OrderCreateMethod::class,
        ];
    }
}
```

### Multi-Tenant Server

```php
use App\Http\Middleware\IdentifyTenant;

class TenantServer extends AbstractServer
{
    #[Override()]
    public function getMiddleware(): array
    {
        return [
            ...parent::getMiddleware(),
            IdentifyTenant::class,
        ];
    }

    #[Override()]
    public function methods(): array
    {
        return [
            TenantUserListMethod::class,
            TenantDataMethod::class,
        ];
    }
}
```

### Admin-Only Server

```php
use App\Http\Middleware\RequireAdmin;

class AdminServer extends AbstractServer
{
    #[Override()]
    public function getRoutePath(): string
    {
        return '/admin/rpc';
    }

    #[Override()]
    public function getMiddleware(): array
    {
        return [
            ...parent::getMiddleware(),
            'auth:sanctum',
            RequireAdmin::class,
        ];
    }

    #[Override()]
    public function methods(): array
    {
        return [
            AdminUserListMethod::class,
            AdminSettingsMethod::class,
            AdminMetricsMethod::class,
        ];
    }
}
```

## Discovery Endpoint

Every server automatically includes an `rpc.discover` method:

```bash
curl -X POST http://localhost/api/rpc \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "rpc.discover",
    "params": {},
    "id": 1
  }'
```

Returns the complete OpenRPC document describing the server's methods, parameters, return types, and error responses.
