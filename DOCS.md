## Table of Contents

1. [Getting Started](#doc-docs-readme) (`docs/README.md`)
2. [Servers](#doc-docs-servers) (`docs/servers.md`)
3. [Methods](#doc-docs-methods) (`docs/methods.md`)
4. [Clients](#doc-docs-clients) (`docs/clients.md`)
5. [Testing](#doc-docs-testing) (`docs/testing.md`)
<a id="doc-docs-readme"></a>

## Installation

Install via Composer:

```bash
composer require cline/rpc
```

## What is RPC?

RPC (Remote Procedure Call) allows you to invoke functions on a remote server as if they were local. This package provides:

- **JSON-RPC 2.0** protocol support with full specification compliance
- **XML-RPC** protocol support for legacy integrations
- **OpenRPC** specification for automatic API documentation
- **Type-safe** clients and servers with Laravel Data integration
- **Laravel integration** with routing, middleware, and validation

## Quick Start

### Create Your First RPC Method

Create a method class that extends `AbstractMethod`:

```php
<?php

namespace App\RPC\Methods;

use Cline\RPC\Methods\AbstractMethod;
use Override;

class HelloMethod extends AbstractMethod
{
    #[Override()]
    public function getName(): string
    {
        return 'app.hello';
    }

    #[Override()]
    public function getSummary(): string
    {
        return 'Returns a greeting message';
    }

    public function __invoke(): array
    {
        return [
            'message' => 'Hello from JSON-RPC!',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### Create an RPC Server

Extend `AbstractServer` to define your API endpoint:

```php
<?php

namespace App\RPC\Servers;

use App\RPC\Methods\HelloMethod;
use Cline\RPC\Servers\AbstractServer;
use Override;

class ApiServer extends AbstractServer
{
    #[Override()]
    public function getName(): string
    {
        return 'My API Server';
    }

    #[Override()]
    public function getRoutePath(): string
    {
        return '/api/rpc';
    }

    #[Override()]
    public function getRouteName(): string
    {
        return 'api.rpc';
    }

    #[Override()]
    public function methods(): array
    {
        return [
            HelloMethod::class,
        ];
    }
}
```

### Register the Server

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Cline\RPC\ServiceProvider"
```

Register your server in `config/rpc.php`:

```php
return [
    'servers' => [
        App\RPC\Servers\ApiServer::class,
    ],
];
```

### Make Your First RPC Call

Use cURL to test your endpoint:

```bash
curl -X POST http://localhost/api/rpc \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "app.hello",
    "params": {},
    "id": 1
  }'
```

Response:

```json
{
  "jsonrpc": "2.0",
  "result": {
    "message": "Hello from JSON-RPC!",
    "timestamp": "2025-01-15T10:30:00Z"
  },
  "id": 1
}
```

## OpenRPC Discovery

Every server automatically includes an `rpc.discover` method that returns the OpenRPC specification:

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

This returns the complete OpenRPC document describing all methods, parameters, return types, and errors.

## Configuration-Based Servers

Instead of creating server classes, you can define servers in configuration:

```php
// config/rpc.php
return [
    'configuration' => [
        'servers' => [
            [
                'name' => 'My API Server',
                'path' => '/api/rpc',
                'route' => 'api.rpc',
                'version' => '1.0.0',
                'middleware' => [],
                'methods' => [
                    App\RPC\Methods\HelloMethod::class,
                ],
            ],
        ],
    ],
];
```

Configuration-based servers support auto-discovery of methods from a directory:

```php
'configuration' => [
    'servers' => [
        [
            'name' => 'My API Server',
            'path' => '/api/rpc',
            'route' => 'api.rpc',
            'version' => '1.0.0',
            'methods' => null, // Auto-discover from paths.methods
        ],
    ],
],
'paths' => [
    'methods' => app_path('RPC/Methods'),
],
'namespaces' => [
    'methods' => 'App\\RPC\\Methods',
],
```

## Error Handling

JSON-RPC 2.0 defines standard error codes:

```php
use Cline\RPC\Exceptions\MethodNotFoundException;
use Cline\RPC\Exceptions\InvalidParamsException;

class UserGetMethod extends AbstractMethod
{
    public function __invoke(int $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            throw new InvalidParamsException('User not found');
        }

        return $user->toArray();
    }
}
```

Standard error response:

```json
{
  "jsonrpc": "2.0",
  "error": {
    "code": -32602,
    "message": "User not found"
  },
  "id": 1
}
```

## Next Steps

- **[Servers](servers)** - Learn how to configure servers, middleware, and OpenRPC schemas
- **[Methods](methods)** - Build methods with validation, authentication, and resources
- **[Clients](clients)** - Create type-safe RPC clients with Saloon integration
- **[Testing](testing)** - Test JSON-RPC endpoints with Pest helper functions

<a id="doc-docs-servers"></a>

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

<a id="doc-docs-methods"></a>

## Creating a Method

### Basic Method

Extend `AbstractMethod` to create a custom RPC method:

```php
<?php

namespace App\RPC\Methods;

use Cline\RPC\Methods\AbstractMethod;
use Override;

class UserGetMethod extends AbstractMethod
{
    #[Override()]
    public function getName(): string
    {
        return 'app.user_get';
    }

    #[Override()]
    public function getSummary(): string
    {
        return 'Retrieve a user by ID';
    }

    public function __invoke(int $userId): array
    {
        $user = User::findOrFail($userId);

        return $user->toArray();
    }
}
```

### Method Name Generation

By default, method names are generated from the class name:

```php
// Class: UserListMethod
// Method name: app.user_list_method

// Override to customize:
#[Override()]
public function getName(): string
{
    return 'users.list';
}
```

## Method Parameters

### Using Request Object

Access request parameters via the `$requestObject` property:

```php
public function __invoke(): array
{
    $userId = $this->requestObject->params['user_id'];
    $includeDeleted = $this->requestObject->params['include_deleted'] ?? false;

    $query = User::query();

    if ($includeDeleted) {
        $query->withTrashed();
    }

    return $query->findOrFail($userId)->toArray();
}
```

### Type-Safe Parameters

Use method parameters for automatic type validation:

```php
public function __invoke(
    int $userId,
    bool $includeRelated = false,
): array {
    $user = User::findOrFail($userId);

    if ($includeRelated) {
        $user->load(['posts', 'comments']);
    }

    return $user->toArray();
}
```

### Laravel Data Objects

Use Spatie's Laravel Data for complex parameter validation:

```php
use Spatie\LaravelData\Data;

class CreateUserParams extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
    ) {}
}

class UserCreateMethod extends AbstractMethod
{
    public function __invoke(CreateUserParams $params): array
    {
        $user = User::create([
            'name' => $params->name,
            'email' => $params->email,
            'password' => Hash::make($params->password),
            'phone' => $params->phone,
        ]);

        return $user->toArray();
    }
}
```

## List Methods

### Creating a List Method

Extend `AbstractListMethod` for paginated list endpoints:

```php
<?php

namespace App\RPC\Methods;

use App\RPC\Resources\UserResource;
use Cline\RPC\Methods\AbstractListMethod;
use Override;

class UserListMethod extends AbstractListMethod
{
    #[Override()]
    public function getName(): string
    {
        return 'app.user_list';
    }

    #[Override()]
    public function getSummary(): string
    {
        return 'Retrieve a paginated list of users';
    }

    #[Override()]
    protected function getResourceClass(): string
    {
        return UserResource::class;
    }
}
```

### Cursor Pagination

List methods automatically support cursor pagination:

```json
{
  "jsonrpc": "2.0",
  "method": "app.user_list",
  "params": {
    "cursor": {
      "limit": 20,
      "cursor": null
    }
  },
  "id": 1
}
```

Response:

```json
{
  "jsonrpc": "2.0",
  "result": {
    "data": [
      { "id": 1, "name": "Alice" },
      { "id": 2, "name": "Bob" }
    ],
    "meta": {
      "next_cursor": "eyJpZCI6MjB9",
      "has_more": true
    }
  },
  "id": 1
}
```

### Custom Query Logic

Override the resource class's query method for custom filtering:

```php
class UserResource extends AbstractModelResource
{
    #[Override()]
    public static function query(): Builder
    {
        return User::query()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');
    }
}
```

## Resources

### Data Resources

Transform Spatie Laravel Data objects into RPC responses:

```php
<?php

namespace App\RPC\Resources;

use App\Data\UserData;
use Cline\RPC\Resources\AbstractDataResource;

class UserResource extends AbstractDataResource
{
    // Automatically derives resource type from Data class name
    // UserData becomes 'user'

    // getId() extracts from $model->id
    // getAttributes() returns $model->toArray()
    // No relationships by default
}
```

Usage:

```php
use App\Data\UserData;
use App\RPC\Resources\UserResource;

class UserGetMethod extends AbstractMethod
{
    public function __invoke(int $userId): array
    {
        $userData = UserData::from(User::findOrFail($userId));

        return (new UserResource($userData))->toArray();
    }
}
```

### Model Resources

Transform Eloquent models into RPC responses:

```php
<?php

namespace App\RPC\Resources;

use App\Models\User;
use Cline\RPC\Resources\AbstractModelResource;
use Override;

class UserResource extends AbstractModelResource
{
    #[Override()]
    public static function model(): string
    {
        return User::class;
    }

    #[Override()]
    public static function getFields(): array
    {
        return ['id', 'name', 'email', 'created_at', 'updated_at'];
    }

    #[Override()]
    public static function getFilters(): array
    {
        return ['status', 'role', 'created_after'];
    }

    #[Override()]
    public static function getRelationships(): array
    {
        return ['posts', 'comments', 'profile'];
    }

    #[Override()]
    public static function getSorts(): array
    {
        return ['name', 'email', 'created_at'];
    }

    #[Override()]
    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'email' => $this->model->email,
            'avatar' => $this->model->avatar_url,
            'created_at' => $this->model->created_at->toIso8601String(),
        ];
    }
}
```

### Field Selection

Clients can request specific fields:

```json
{
  "params": {
    "fields": ["id", "name", "email"]
  }
}
```

### Filtering

Apply filters to narrow results:

```json
{
  "params": {
    "filters": {
      "status": "active",
      "role": "admin"
    }
  }
}
```

### Relationship Loading

Include related data:

```json
{
  "params": {
    "relationships": ["posts", "profile"]
  }
}
```

### Sorting

Sort results by specified fields:

```json
{
  "params": {
    "sorts": [
      { "field": "created_at", "direction": "desc" }
    ]
  }
}
```

## Authentication

### Using the Authentication Trait

Access authenticated user via `InteractsWithAuthentication`:

```php
use Cline\RPC\Methods\AbstractMethod;

class UserProfileMethod extends AbstractMethod
{
    public function __invoke(): array
    {
        $user = $this->getUser();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
```

### Checking Abilities

```php
public function __invoke(int $postId): array
{
    $user = $this->getUser();

    if (!$user->can('delete', Post::class)) {
        throw new UnauthorizedException('Cannot delete posts');
    }

    Post::findOrFail($postId)->delete();

    return ['success' => true];
}
```

## OpenRPC Documentation

### Parameter Descriptors

Define parameters for OpenRPC documentation:

```php
use Cline\OpenRpc\ContentDescriptor\ContentDescriptor;
use Cline\OpenRpc\Schema\IntegerSchema;
use Cline\OpenRpc\Schema\StringSchema;

#[Override()]
public function getParams(): array
{
    return [
        ContentDescriptor::create()
            ->name('user_id')
            ->required(true)
            ->schema(IntegerSchema::create()->minimum(1)),
        ContentDescriptor::create()
            ->name('include_deleted')
            ->required(false)
            ->schema(['type' => 'boolean']),
    ];
}
```

### Result Descriptors

Document return values:

```php
use Cline\OpenRpc\ValueObject\ContentDescriptorValue;
use Cline\OpenRpc\Schema\ObjectSchema;

#[Override()]
public function getResult(): ?ContentDescriptorValue
{
    return ContentDescriptorValue::create()
        ->name('result')
        ->schema(
            ObjectSchema::create()
                ->property('id', 'integer')
                ->property('name', 'string')
                ->property('email', 'string')
                ->property('created_at', 'string')
                ->required(['id', 'name', 'email'])
        );
}
```

### Error Descriptors

Document possible errors:

```php
use Cline\OpenRpc\ValueObject\ErrorValue;

#[Override()]
public function getErrors(): array
{
    return [
        ErrorValue::create()
            ->code(-32001)
            ->message('User not found'),
        ErrorValue::create()
            ->code(-32002)
            ->message('Insufficient permissions'),
    ];
}
```

## Error Handling

### Standard Exceptions

Use built-in JSON-RPC exceptions:

```php
use Cline\RPC\Exceptions\InvalidParamsException;
use Cline\RPC\Exceptions\MethodNotFoundException;

public function __invoke(int $userId): array
{
    if ($userId < 1) {
        throw new InvalidParamsException('User ID must be positive');
    }

    $user = User::find($userId);

    if (!$user) {
        throw new InvalidParamsException('User not found');
    }

    return $user->toArray();
}
```

### Custom Exceptions

Create custom RPC exceptions:

```php
<?php

namespace App\RPC\Exceptions;

use Cline\RPC\Exceptions\AbstractRequestException;

class UserNotFoundException extends AbstractRequestException
{
    public function __construct()
    {
        parent::__construct(
            code: -32001,
            message: 'User not found',
        );
    }
}
```

Usage:

```php
public function __invoke(int $userId): array
{
    $user = User::find($userId);

    if (!$user) {
        throw new UserNotFoundException();
    }

    return $user->toArray();
}
```

## Practical Examples

### CRUD Methods

```php
// Create
class UserCreateMethod extends AbstractMethod
{
    public function __invoke(CreateUserParams $params): array
    {
        $user = User::create($params->toArray());
        return (new UserResource($user))->toArray();
    }
}

// Read
class UserGetMethod extends AbstractMethod
{
    public function __invoke(int $userId): array
    {
        $user = User::findOrFail($userId);
        return (new UserResource($user))->toArray();
    }
}

// Update
class UserUpdateMethod extends AbstractMethod
{
    public function __invoke(int $userId, UpdateUserParams $params): array
    {
        $user = User::findOrFail($userId);
        $user->update($params->toArray());
        return (new UserResource($user->fresh()))->toArray();
    }
}

// Delete
class UserDeleteMethod extends AbstractMethod
{
    public function __invoke(int $userId): array
    {
        User::findOrFail($userId)->delete();
        return ['success' => true];
    }
}
```

### Search Method

```php
class UserSearchMethod extends AbstractMethod
{
    public function __invoke(string $query, int $limit = 10): array
    {
        $users = User::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit($limit)
            ->get();

        return UserResource::collection($users)->toArray();
    }
}
```

### Batch Operation

```php
class UserBulkUpdateMethod extends AbstractMethod
{
    public function __invoke(array $updates): array
    {
        $results = [];

        foreach ($updates as $update) {
            $user = User::find($update['id']);

            if ($user) {
                $user->update($update['data']);
                $results[] = ['id' => $user->id, 'success' => true];
            } else {
                $results[] = ['id' => $update['id'], 'success' => false];
            }
        }

        return $results;
    }
}
```

<a id="doc-docs-clients"></a>

## Creating a Client

### JSON-RPC Client

Create a client using the static factory method:

```php
use Cline\RPC\Clients\Client;

$client = Client::json('https://api.example.com/rpc');
```

### XML-RPC Client

For legacy XML-RPC endpoints:

```php
$client = Client::xml('https://api.example.com/xmlrpc');
```

### Generic Client

Create a client with custom protocol:

```php
use Cline\RPC\Protocols\JsonRpcProtocol;

$client = Client::create(
    'https://api.example.com/rpc',
    new JsonRpcProtocol()
);
```

## Single Requests

### Basic Request

Make a single RPC call:

```php
use Cline\RPC\Data\RequestObjectData;

$client = Client::json('https://api.example.com/rpc');

$request = RequestObjectData::asRequest(
    'app.user_get',
    ['user_id' => 42],
    1
);

$response = $client->add($request)->request();

if ($response->hasResult()) {
    $user = $response->result;
    echo $user['name'];
}

if ($response->hasError()) {
    $error = $response->error;
    echo "Error {$error->code}: {$error->message}";
}
```

### Without ID (Notification)

Send a notification (no response expected):

```php
$request = RequestObjectData::asNotification(
    'app.log_event',
    ['event' => 'user_login', 'user_id' => 42]
);

$client->add($request)->request();
```

### Inline Request Creation

```php
$response = $client
    ->add(
        RequestObjectData::asRequest(
            'app.user_get',
            ['user_id' => 42],
            1
        )
    )
    ->request();
```

## Batch Requests

### Multiple Requests

Execute multiple requests in a single HTTP call:

```php
$client = Client::json('https://api.example.com/rpc');

$responses = $client
    ->add(
        RequestObjectData::asRequest(
            'app.user_get',
            ['user_id' => 1],
            1
        )
    )
    ->add(
        RequestObjectData::asRequest(
            'app.user_get',
            ['user_id' => 2],
            2
        )
    )
    ->add(
        RequestObjectData::asRequest(
            'app.user_list',
            ['cursor' => ['limit' => 10]],
            3
        )
    )
    ->request();

// Responses is a DataCollection
foreach ($responses as $response) {
    if ($response->hasResult()) {
        var_dump($response->result);
    }
}
```

### Using addMany()

Add multiple requests at once:

```php
$requests = [
    RequestObjectData::asRequest(
        'app.user_get',
        ['user_id' => 1],
        1
    ),
    RequestObjectData::asRequest(
        'app.user_get',
        ['user_id' => 2],
        2
    ),
];

$responses = $client->addMany($requests)->request();
```

### Matching Responses to Requests

Match responses by ID:

```php
$responses = $client
    ->add(
        RequestObjectData::asRequest(
            'app.user_get',
            ['user_id' => 1],
            'user-1'
        )
    )
    ->add(
        RequestObjectData::asRequest(
            'app.post_get',
            ['post_id' => 100],
            'post-100'
        )
    )
    ->request();

foreach ($responses as $response) {
    match ($response->id) {
        'user-1' => $this->handleUser($response->result),
        'post-100' => $this->handlePost($response->result),
    };
}
```

## Response Handling

### Checking for Success

```php
$response = $client->add($request)->request();

if ($response->hasResult()) {
    // Success - process result
    $data = $response->result;
}

if ($response->hasError()) {
    // Error occurred
    $error = $response->error;
    echo "Code: {$error->code}";
    echo "Message: {$error->message}";

    if ($error->data) {
        var_dump($error->data);
    }
}
```

### Standard Error Codes

JSON-RPC 2.0 defines standard error codes:

```php
if ($response->hasError()) {
    $error = $response->error;

    match ($error->code) {
        -32700 => 'Parse error',
        -32600 => 'Invalid request',
        -32601 => 'Method not found',
        -32602 => 'Invalid params',
        -32603 => 'Internal error',
        default => "Application error: {$error->message}",
    };
}
```

## Working with Pagination

### Fetching Paginated Results

```php
$client = Client::json('https://api.example.com/rpc');

$cursor = null;
$allUsers = [];

do {
    $response = $client
        ->add(
            RequestObjectData::asRequest(
                'app.user_list',
                [
                    'cursor' => [
                        'limit' => 50,
                        'cursor' => $cursor,
                    ],
                ],
                1
            )
        )
        ->request();

    $result = $response->result;
    $allUsers = array_merge($allUsers, $result['data']);

    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($cursor !== null);

echo "Fetched " . count($allUsers) . " users";
```

### Parallel Pagination

Fetch multiple pages simultaneously:

```php
$client = Client::json('https://api.example.com/rpc');

// Fetch first 3 pages in parallel
$responses = $client
    ->add(
        RequestObjectData::asRequest(
            'app.user_list',
            ['cursor' => ['limit' => 50, 'cursor' => null]],
            1
        )
    )
    ->add(
        RequestObjectData::asRequest(
            'app.user_list',
            ['cursor' => ['limit' => 50, 'cursor' => 'page2']],
            2
        )
    )
    ->add(
        RequestObjectData::asRequest(
            'app.user_list',
            ['cursor' => ['limit' => 50, 'cursor' => 'page3']],
            3
        )
    )
    ->request();

$allUsers = $responses->flatMap(fn($r) => $r->result['data']);
```

## Advanced Usage

### Custom HTTP Configuration

Customize the underlying HTTP client:

```php
use Illuminate\Support\Facades\Http;

$client = new Client(
    host: 'https://api.example.com/rpc',
    protocol: new JsonRpcProtocol()
);

// Access the underlying HTTP client
$httpClient = Http::baseUrl('https://api.example.com/rpc')
    ->timeout(30)
    ->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ])
    ->retry(3, 100);
```

### Error Recovery

Handle errors gracefully with retries:

```php
function callWithRetry(Client $client, RequestObjectData $request, int $maxRetries = 3): mixed
{
    $attempt = 0;

    while ($attempt < $maxRetries) {
        $response = $client->add($request)->request();

        if ($response->hasResult()) {
            return $response->result;
        }

        if ($response->hasError()) {
            $error = $response->error;

            // Don't retry client errors
            if ($error->code >= -32602 && $error->code <= -32600) {
                throw new Exception("Client error: {$error->message}");
            }

            // Retry server errors
            $attempt++;
            sleep(pow(2, $attempt)); // Exponential backoff
        }
    }

    throw new Exception('Max retries exceeded');
}
```

## Practical Examples

### Type-Safe Client Wrapper

Create a typed client for your API:

```php
<?php

namespace App\Services;

use Cline\RPC\Clients\Client;
use Cline\RPC\Data\RequestObjectData;

class UserApiClient
{
    private Client $client;
    private int $requestId = 1;

    public function __construct(string $baseUrl, string $apiToken)
    {
        $this->client = Client::json($baseUrl);
        // Configure authentication here
    }

    public function getUser(int $userId): array
    {
        $response = $this->client
            ->add(
                RequestObjectData::asRequest(
                    'app.user_get',
                    ['user_id' => $userId],
                    $this->requestId++
                )
            )
            ->request();

        if ($response->hasError()) {
            throw new Exception($response->error->message);
        }

        return $response->result;
    }

    public function listUsers(int $limit = 20, ?string $cursor = null): array
    {
        $response = $this->client
            ->add(
                RequestObjectData::asRequest(
                    'app.user_list',
                    [
                        'cursor' => [
                            'limit' => $limit,
                            'cursor' => $cursor,
                        ],
                    ],
                    $this->requestId++
                )
            )
            ->request();

        if ($response->hasError()) {
            throw new Exception($response->error->message);
        }

        return $response->result;
    }

    public function createUser(array $userData): array
    {
        $response = $this->client
            ->add(
                RequestObjectData::asRequest(
                    'app.user_create',
                    $userData,
                    $this->requestId++
                )
            )
            ->request();

        if ($response->hasError()) {
            throw new Exception($response->error->message);
        }

        return $response->result;
    }

    public function batchGetUsers(array $userIds): array
    {
        $client = $this->client;

        foreach ($userIds as $userId) {
            $client->add(
                RequestObjectData::asRequest(
                    'app.user_get',
                    ['user_id' => $userId],
                    $this->requestId++
                )
            );
        }

        $responses = $client->request();

        return $responses
            ->filter(fn($r) => $r->hasResult())
            ->map(fn($r) => $r->result)
            ->all();
    }
}
```

Usage:

```php
$api = new UserApiClient('https://api.example.com/rpc', 'token');

// Get single user
$user = $api->getUser(42);

// List users
$users = $api->listUsers(limit: 50);

// Create user
$newUser = $api->createUser([
    'name' => 'Alice',
    'email' => 'alice@example.com',
]);

// Batch fetch
$users = $api->batchGetUsers([1, 2, 3, 4, 5]);
```

### Caching Client

Add caching to reduce API calls:

```php
use Illuminate\Support\Facades\Cache;

class CachedUserApiClient extends UserApiClient
{
    public function getUser(int $userId): array
    {
        return Cache::remember(
            "user.{$userId}",
            now()->addMinutes(5),
            fn() => parent::getUser($userId)
        );
    }
}
```

### Async Client

Process batch requests asynchronously:

```php
use Illuminate\Support\Facades\Http;

$promises = collect([1, 2, 3, 4, 5])->mapWithKeys(function ($userId) {
    return [
        "user-{$userId}" => Http::async()
            ->post('https://api.example.com/rpc', [
                'jsonrpc' => '2.0',
                'method' => 'app.user_get',
                'params' => ['user_id' => $userId],
                'id' => $userId,
            ]),
    ];
});

$responses = Http::pool(fn($pool) => $promises->all());

foreach ($responses as $key => $response) {
    $data = $response->json();
    if (isset($data['result'])) {
        echo "Got user data for {$key}\n";
    }
}
```

<a id="doc-docs-testing"></a>

## Testing RPC Endpoints

### Using post_json_rpc()

The `post_json_rpc()` helper simplifies testing JSON-RPC endpoints in Pest tests:

```php
use function Cline\RPC\post_json_rpc;

it('retrieves a user by ID', function () {
    $user = User::factory()->create();

    post_json_rpc('app.user_get', ['user_id' => $user->id])
        ->assertOk()
        ->assertJson([
            'jsonrpc' => '2.0',
            'result' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
});
```

### Basic Usage

Simple RPC call without parameters:

```php
it('lists all users', function () {
    User::factory()->count(3)->create();

    post_json_rpc('app.user_list')
        ->assertOk()
        ->assertJsonStructure([
            'jsonrpc',
            'result' => [
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
                'meta',
            ],
            'id',
        ]);
});
```

### With Parameters

RPC call with parameters:

```php
it('creates a new user', function () {
    post_json_rpc('app.user_create', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'secret123',
    ])
        ->assertOk()
        ->assertJson([
            'result' => [
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ],
        ]);

    expect(User::where('email', 'alice@example.com')->exists())->toBeTrue();
});
```

### Custom Request ID

Use custom request IDs for correlation:

```php
it('handles custom request IDs', function () {
    post_json_rpc('app.user_list', null, 'custom-id-123')
        ->assertOk()
        ->assertJson(['id' => 'custom-id-123']);
});
```

## Testing Error Responses

### Invalid Parameters

```php
it('returns error for invalid user ID', function () {
    post_json_rpc('app.user_get', ['user_id' => -1])
        ->assertOk()
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32602,
                'message' => 'User ID must be positive',
            ],
        ]);
});
```

### Method Not Found

```php
it('returns error for unknown method', function () {
    post_json_rpc('app.nonexistent_method')
        ->assertOk()
        ->assertJson([
            'error' => [
                'code' => -32601,
                'message' => 'Method not found',
            ],
        ]);
});
```

## Testing Authentication

### Authenticated Methods

```php
use Laravel\Sanctum\Sanctum;

it('requires authentication', function () {
    post_json_rpc('app.user_profile')
        ->assertOk()
        ->assertJsonStructure(['error']);
});

it('returns user profile when authenticated', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    post_json_rpc('app.user_profile')
        ->assertOk()
        ->assertJson([
            'result' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
});
```

## Testing Batch Requests

Test multiple RPC calls in a single request:

```php
use function Pest\Laravel\postJson;

it('handles batch requests', function () {
    $users = User::factory()->count(3)->create();

    postJson(route('rpc'), [
        [
            'jsonrpc' => '2.0',
            'method' => 'app.user_get',
            'params' => ['user_id' => $users[0]->id],
            'id' => 1,
        ],
        [
            'jsonrpc' => '2.0',
            'method' => 'app.user_get',
            'params' => ['user_id' => $users[1]->id],
            'id' => 2,
        ],
    ])
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJson([
            ['id' => 1, 'result' => ['id' => $users[0]->id]],
            ['id' => 2, 'result' => ['id' => $users[1]->id]],
        ]);
});
```

## Testing Pagination

### Cursor Pagination

```php
it('paginates users with cursor', function () {
    User::factory()->count(25)->create();

    $response = post_json_rpc('app.user_list', [
        'cursor' => ['limit' => 10],
    ])
        ->assertOk()
        ->json();

    expect($response['result']['data'])->toHaveCount(10);
    expect($response['result']['meta']['has_more'])->toBeTrue();
    expect($response['result']['meta']['next_cursor'])->not->toBeNull();
});

it('follows pagination cursor', function () {
    User::factory()->count(25)->create();

    // Get first page
    $page1 = post_json_rpc('app.user_list', [
        'cursor' => ['limit' => 10],
    ])->json();

    // Get second page using cursor
    $page2 = post_json_rpc('app.user_list', [
        'cursor' => [
            'limit' => 10,
            'cursor' => $page1['result']['meta']['next_cursor'],
        ],
    ])->json();

    expect($page2['result']['data'])->toHaveCount(10);
    expect($page1['result']['data'][0]['id'])
        ->not->toBe($page2['result']['data'][0]['id']);
});
```

## Testing Filtering and Sorting

### Filters

```php
it('filters users by status', function () {
    User::factory()->create(['status' => 'active']);
    User::factory()->create(['status' => 'inactive']);

    post_json_rpc('app.user_list', [
        'filters' => ['status' => 'active'],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'result.data')
        ->assertJson([
            'result' => [
                'data' => [
                    ['status' => 'active'],
                ],
            ],
        ]);
});
```

### Sorting

```php
it('sorts users by name', function () {
    User::factory()->create(['name' => 'Charlie']);
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    $response = post_json_rpc('app.user_list', [
        'sorts' => [
            ['field' => 'name', 'direction' => 'asc'],
        ],
    ])
        ->assertOk()
        ->json();

    expect($response['result']['data'][0]['name'])->toBe('Alice');
    expect($response['result']['data'][1]['name'])->toBe('Bob');
    expect($response['result']['data'][2]['name'])->toBe('Charlie');
});
```

## Testing Field Selection

```php
it('selects specific fields', function () {
    User::factory()->create();

    post_json_rpc('app.user_list', [
        'fields' => ['id', 'name'],
    ])
        ->assertOk()
        ->assertJsonStructure([
            'result' => [
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ],
        ])
        ->assertJsonMissing(['email']);
});
```

## Testing Relationships

```php
it('includes related data', function () {
    $user = User::factory()
        ->has(Post::factory()->count(3))
        ->create();

    post_json_rpc('app.user_get', [
        'user_id' => $user->id,
        'relationships' => ['posts'],
    ])
        ->assertOk()
        ->assertJsonStructure([
            'result' => [
                'id',
                'name',
                'posts' => [
                    '*' => ['id', 'title'],
                ],
            ],
        ])
        ->assertJsonCount(3, 'result.posts');
});
```

## Custom Assertions

Create reusable assertions for RPC responses:

```php
expect()->extend('toBeValidRpcResponse', function () {
    return $this->value
        ->toHaveKey('jsonrpc')
        ->toHaveKey('id')
        ->and($this->value['jsonrpc'])->toBe('2.0');
});

expect()->extend('toHaveRpcResult', function () {
    return $this->value
        ->toBeValidRpcResponse()
        ->toHaveKey('result')
        ->not->toHaveKey('error');
});

expect()->extend('toHaveRpcError', function (int $code = null) {
    $this->value
        ->toBeValidRpcResponse()
        ->toHaveKey('error')
        ->not->toHaveKey('result');

    if ($code !== null) {
        expect($this->value['error']['code'])->toBe($code);
    }

    return $this;
});
```

Usage:

```php
it('validates RPC response structure', function () {
    $response = post_json_rpc('app.user_list')->json();

    expect($response)->toHaveRpcResult();
});

it('validates RPC error structure', function () {
    $response = post_json_rpc('app.invalid_method')->json();

    expect($response)->toHaveRpcError(-32601);
});
```
