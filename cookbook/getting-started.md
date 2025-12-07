# Getting Started

A comprehensive guide to building JSON-RPC 2.0 and XML-RPC APIs with Laravel integration.

## What is RPC?

RPC (Remote Procedure Call) allows you to invoke functions on a remote server as if they were local. This package provides:

- **JSON-RPC 2.0** protocol support with full specification compliance
- **XML-RPC** protocol support for legacy integrations
- **OpenRPC** specification for automatic API documentation
- **Type-safe** clients and servers with Laravel Data integration
- **Laravel integration** with routing, middleware, and validation

## Installation

Install via Composer:

```bash
composer require cline/rpc
```

## Quick Start

### 1. Create Your First RPC Method

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

### 2. Create an RPC Server

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

### 3. Register the Server

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

### 4. Make Your First RPC Call

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

- **[Servers](servers.md)** - Learn how to configure servers, middleware, and OpenRPC schemas
- **[Methods](methods.md)** - Build methods with validation, authentication, and resources
- **[Clients](clients.md)** - Create type-safe RPC clients with Saloon integration
