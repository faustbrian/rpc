# Clients

Build type-safe RPC clients with fluent APIs for single and batch requests.

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

$request = RequestObjectData::create()
    ->method('app.user_get')
    ->params(['user_id' => 42])
    ->id(1);

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
$request = RequestObjectData::create()
    ->method('app.log_event')
    ->params(['event' => 'user_login', 'user_id' => 42])
    ->id(null); // Null ID = notification

$client->add($request)->request();
```

### Inline Request Creation

```php
$response = $client
    ->add(
        RequestObjectData::create()
            ->method('app.user_get')
            ->params(['user_id' => 42])
            ->id(1)
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
        RequestObjectData::create()
            ->method('app.user_get')
            ->params(['user_id' => 1])
            ->id(1)
    )
    ->add(
        RequestObjectData::create()
            ->method('app.user_get')
            ->params(['user_id' => 2])
            ->id(2)
    )
    ->add(
        RequestObjectData::create()
            ->method('app.user_list')
            ->params(['cursor' => ['limit' => 10]])
            ->id(3)
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
    RequestObjectData::create()
        ->method('app.user_get')
        ->params(['user_id' => 1])
        ->id(1),
    RequestObjectData::create()
        ->method('app.user_get')
        ->params(['user_id' => 2])
        ->id(2),
];

$responses = $client->addMany($requests)->request();
```

### Matching Responses to Requests

Match responses by ID:

```php
$responses = $client
    ->add(
        RequestObjectData::create()
            ->method('app.user_get')
            ->params(['user_id' => 1])
            ->id('user-1')
    )
    ->add(
        RequestObjectData::create()
            ->method('app.post_get')
            ->params(['post_id' => 100])
            ->id('post-100')
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
            RequestObjectData::create()
                ->method('app.user_list')
                ->params([
                    'cursor' => [
                        'limit' => 50,
                        'cursor' => $cursor,
                    ],
                ])
                ->id(1)
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
        RequestObjectData::create()
            ->method('app.user_list')
            ->params(['cursor' => ['limit' => 50, 'cursor' => null]])
            ->id(1)
    )
    ->add(
        RequestObjectData::create()
            ->method('app.user_list')
            ->params(['cursor' => ['limit' => 50, 'cursor' => 'page2']])
            ->id(2)
    )
    ->add(
        RequestObjectData::create()
            ->method('app.user_list')
            ->params(['cursor' => ['limit' => 50, 'cursor' => 'page3']])
            ->id(3)
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
                RequestObjectData::create()
                    ->method('app.user_get')
                    ->params(['user_id' => $userId])
                    ->id($this->requestId++)
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
                RequestObjectData::create()
                    ->method('app.user_list')
                    ->params([
                        'cursor' => [
                            'limit' => $limit,
                            'cursor' => $cursor,
                        ],
                    ])
                    ->id($this->requestId++)
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
                RequestObjectData::create()
                    ->method('app.user_create')
                    ->params($userData)
                    ->id($this->requestId++)
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
                RequestObjectData::create()
                    ->method('app.user_get')
                    ->params(['user_id' => $userId])
                    ->id($this->requestId++)
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
