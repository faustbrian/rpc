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
