# Methods

Build RPC methods with validation, authentication, resources, and OpenRPC documentation.

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
