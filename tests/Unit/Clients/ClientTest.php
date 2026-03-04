<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Clients\Client;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Data\ResponseData;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;

describe('Client', function (): void {
    describe('Happy Paths', function (): void {
        test('creates client instance', function (): void {
            // Arrange & Act
            $client = Client::create('https://api.example.com');

            // Assert
            expect($client)->toBeInstanceOf(Client::class);
        });

        test('creates JSON-RPC client instance', function (): void {
            // Arrange & Act
            $client = Client::json('https://api.example.com');

            // Assert
            expect($client)->toBeInstanceOf(Client::class);
        });

        test('creates XML-RPC client instance', function (): void {
            // Arrange & Act
            $client = Client::xml('https://api.example.com');

            // Assert
            expect($client)->toBeInstanceOf(Client::class);
        });

        test('adds request to batch', function (): void {
            // Arrange
            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'method' => 'test.method',
                'params' => ['param' => 'value'],
                'id' => 1,
            ]);

            // Act
            $result = $client->add($request);

            // Assert
            expect($result)->toBeInstanceOf(Client::class);
        });

        test('executes single request successfully', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['status' => 'success'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'method' => 'test.method',
                'params' => ['param' => 'value'],
                'id' => 1,
            ]);

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->jsonrpc)->toBe('2.0');
            expect($response->id)->toBe(1);
            expect($response->result)->toBe(['status' => 'success']);
            expect($response->error)->toBeNull();
        });

        test('executes batch request with multiple requests successfully', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => ['status' => 'success'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 2,
                        'result' => ['status' => 'completed'],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $requests = [
                RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'method' => 'test.method1',
                    'params' => ['param' => 'value1'],
                    'id' => 1,
                ]),
                RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'method' => 'test.method2',
                    'params' => ['param' => 'value2'],
                    'id' => 2,
                ]),
            ];

            // Act
            $response = $client->addMany($requests)->request();

            // Assert
            expect($response)->toBeInstanceOf(DataCollection::class);
            expect($response)->toHaveCount(2);
            expect($response->first())->toBeInstanceOf(ResponseData::class);
            expect($response->first()->id)->toBe(1);
            expect($response->last())->toBeInstanceOf(ResponseData::class);
            expect($response->last()->id)->toBe(2);
        });

        test('addMany returns client instance for method chaining', function (): void {
            // Arrange
            $client = Client::create('https://api.example.com');
            $requests = [
                RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'method' => 'test.method',
                    'params' => ['param' => 'value'],
                    'id' => 1,
                ]),
            ];

            // Act
            $result = $client->addMany($requests);

            // Assert
            expect($result)->toBeInstanceOf(Client::class);
        });

        test('parses http response correctly for single request', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 'test-id',
                    'result' => ['user_id' => 123, 'username' => 'testuser'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('getUser', ['id' => 123]);

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->result)->toBe(['user_id' => 123, 'username' => 'testuser']);
            expect($response->isSuccessful())->toBeTrue();
        });

        test('parses http response correctly for batch request', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => ['data' => 'first'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 2,
                        'result' => ['data' => 'second'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 3,
                        'result' => ['data' => 'third'],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $requests = [
                RequestObjectData::asRequest('method1', [], 1),
                RequestObjectData::asRequest('method2', [], 2),
                RequestObjectData::asRequest('method3', [], 3),
            ];

            // Act
            $responses = $client->addMany($requests)->request();

            // Assert
            expect($responses)->toBeInstanceOf(DataCollection::class);
            expect($responses)->toHaveCount(3);
            expect($responses[0]->result)->toBe(['data' => 'first']);
            expect($responses[1]->result)->toBe(['data' => 'second']);
            expect($responses[2]->result)->toBe(['data' => 'third']);
        });

        test('sends POST request to configured endpoint', function (): void {
            // Arrange
            Http::fake([
                'api.example.com/*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 'test-id',
                    'result' => ['success' => true],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('test.method', ['param' => 'value'], 'test-id');

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            Http::assertSentCount(1);
        });

        test('sends single request as object not array in http body', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['data' => 'test'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('test.method', ['key' => 'value'], 1);

            // Act
            $client->add($request)->request();

            // Assert
            Http::assertSent(function ($sentRequest): bool {
                $body = $sentRequest->data();

                return array_key_exists('jsonrpc', $body)
                       && $body['jsonrpc'] === '2.0'
                       && $body['method'] === 'test.method'
                       && $body['id'] === 1;
            });
        });

        test('sends batch requests as array in http body', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => ['data' => 'first'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 2,
                        'result' => ['data' => 'second'],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $requests = [
                RequestObjectData::asRequest('method1', [], 1),
                RequestObjectData::asRequest('method2', [], 2),
            ];

            // Act
            $client->addMany($requests)->request();

            // Assert
            Http::assertSent(function ($sentRequest): bool {
                $body = $sentRequest->data();

                return is_array($body)
                       && isset($body[0]['jsonrpc'], $body[1]['jsonrpc'])
                       && count($body) === 2;
            });
        });

        test('sends http request to root path', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['status' => 'ok'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('ping', null, 1);

            // Act
            $client->add($request)->request();

            // Assert
            Http::assertSent(fn ($sentRequest): bool => str_ends_with((string) $sentRequest->url(), '/'));
        });

        test('uses base url configuration for http client', function (): void {
            // Arrange
            Http::fake([
                'custom.api.com/*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['configured' => true],
                ], 200),
            ]);

            $client = Client::create('https://custom.api.com');
            $request = RequestObjectData::asRequest('test.config', null, 1);

            // Act
            $client->add($request)->request();

            // Assert
            Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'custom.api.com'));
        });

        test('configures http client with json content type', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['success' => true],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('test', null, 1);

            // Act
            $client->add($request)->request();

            // Assert
            Http::assertSent(function ($sentRequest): bool {
                $headers = $sentRequest->headers();

                return array_key_exists('Content-Type', $headers)
                       && str_contains($headers['Content-Type'][0] ?? '', 'application/json');
            });
        });
    });

    describe('Sad Paths', function (): void {
        test('handles http error response', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'error' => [
                        'code' => -32_601,
                        'message' => 'Method not found',
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('nonexistent.method');

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->error)->not->toBeNull();
            expect($response->error->code)->toBe(-32_601);
            expect($response->error->message)->toBe('Method not found');
            expect($response->isSuccessful())->toBeFalse();
        });

        test('handles batch request with mixed success and error responses', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => ['status' => 'success'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 2,
                        'error' => [
                            'code' => -32_602,
                            'message' => 'Invalid params',
                        ],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $requests = [
                RequestObjectData::asRequest('valid.method', ['valid' => true], 1),
                RequestObjectData::asRequest('invalid.method', ['invalid' => true], 2),
            ];

            // Act
            $responses = $client->addMany($requests)->request();

            // Assert
            expect($responses)->toBeInstanceOf(DataCollection::class);
            expect($responses)->toHaveCount(2);
            expect($responses->first()->isSuccessful())->toBeTrue();
            expect($responses->last()->isSuccessful())->toBeFalse();
            expect($responses->last()->error->code)->toBe(-32_602);
        });
    });

    describe('Edge Cases', function (): void {
        test('handles addMany with single request for method chaining', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['success' => true],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $singleRequestArray = [
                RequestObjectData::asRequest('single.method', ['param' => 'value'], 1),
            ];

            // Act
            $response = $client->addMany($singleRequestArray)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->result)->toBe(['success' => true]);
        });

        test('handles addMany with empty array', function (): void {
            // Arrange
            $client = Client::create('https://api.example.com');
            $emptyRequests = [];

            // Act
            $result = $client->addMany($emptyRequests);

            // Assert
            expect($result)->toBeInstanceOf(Client::class);
        });

        test('handles empty response array from server', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => null,
                    'result' => null,
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('test.method');

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
        });

        test('handles response with null result', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => null,
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('delete.user', ['id' => 123]);

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->result)->toBeNull();
            expect($response->error)->toBeNull();
            expect($response->isSuccessful())->toBeTrue();
        });

        test('handles notification requests without id', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => null,
                    'result' => null,
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asNotification('log.event', ['event' => 'user_login']);

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->id)->toBeNull();
        });

        test('handles complex nested result data', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => [
                        'user' => [
                            'id' => 123,
                            'profile' => [
                                'name' => 'John Doe',
                                'emails' => ['john@example.com', 'doe@example.com'],
                            ],
                        ],
                        'metadata' => [
                            'timestamp' => '2025-10-11T00:00:00Z',
                        ],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('getComplexData');

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->result['user']['profile']['name'])->toBe('John Doe');
            expect($response->result['user']['profile']['emails'])->toHaveCount(2);
        });

        test('handles request with no params', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['status' => 'ok'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('getStatus');

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->result)->toBe(['status' => 'ok']);
        });

        test('handles unicode characters in response', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['message' => '你好世界 🌍', 'emoji' => '😀'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('getUnicodeData');

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->result['message'])->toBe('你好世界 🌍');
            expect($response->result['emoji'])->toBe('😀');
        });

        test('handles very large batch request', function (): void {
            // Arrange
            $responseData = [];

            for ($i = 1; $i <= 100; ++$i) {
                $responseData[] = [
                    'jsonrpc' => '2.0',
                    'id' => $i,
                    'result' => ['index' => $i],
                ];
            }

            Http::fake([
                '*' => Http::response($responseData, 200),
            ]);

            $client = Client::create('https://api.example.com');
            $requests = [];

            for ($i = 1; $i <= 100; ++$i) {
                $requests[] = RequestObjectData::asRequest('process', ['index' => $i], $i);
            }

            // Act
            $responses = $client->addMany($requests)->request();

            // Assert
            expect($responses)->toBeInstanceOf(DataCollection::class);
            expect($responses)->toHaveCount(100);
            expect($responses->first()->id)->toBe(1);
            expect($responses->last()->id)->toBe(100);
        });

        test('handles request with different host configurations', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 'test-id',
                    'result' => ['success' => true],
                ], 200),
            ]);

            $clientWithPort = Client::create('https://api.example.com:8080');
            $clientWithPath = Client::create('https://api.example.com/v1/rpc');
            $request = RequestObjectData::asRequest('test', [], 'test-id');

            // Act
            $clientWithPort->add($request)->request();
            $clientWithPath->add($request)->request();

            // Assert
            Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.example.com:8080'));
            Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.example.com/v1/rpc'));
        });

        test('determines single request correctly when only one request added', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['type' => 'single'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('test', null, 1);

            // Act
            $response = $client->add($request)->request();

            // Assert - Should return ResponseData, not DataCollection
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response)->not->toBeInstanceOf(DataCollection::class);
            expect($response->result)->toBe(['type' => 'single']);
        });

        test('determines batch request correctly when exactly two requests added', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => ['type' => 'batch'],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 2,
                        'result' => ['type' => 'batch'],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $client->add(RequestObjectData::asRequest('test1', null, 1));
            $client->add(RequestObjectData::asRequest('test2', null, 2));

            // Act
            $response = $client->request();

            // Assert - Should return DataCollection, not single ResponseData
            expect($response)->toBeInstanceOf(DataCollection::class);
            expect($response)->not->toBeInstanceOf(ResponseData::class);
            expect($response)->toHaveCount(2);
        });

        test('handles transition from single to batch by adding second request', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 1,
                        'result' => ['first' => true],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 2,
                        'result' => ['second' => true],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');

            // Act - Add first request (would be single)
            $client->add(RequestObjectData::asRequest('method1', null, 1));
            // Add second request (now becomes batch)
            $client->add(RequestObjectData::asRequest('method2', null, 2));

            $response = $client->request();

            // Assert - Should be batch
            expect($response)->toBeInstanceOf(DataCollection::class);
            expect($response)->toHaveCount(2);
        });

        test('collects batch responses into data collection correctly', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    [
                        'jsonrpc' => '2.0',
                        'id' => 'a',
                        'result' => ['order' => 1],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 'b',
                        'result' => ['order' => 2],
                    ],
                    [
                        'jsonrpc' => '2.0',
                        'id' => 'c',
                        'result' => ['order' => 3],
                    ],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $requests = [
                RequestObjectData::asRequest('test', null, 'a'),
                RequestObjectData::asRequest('test', null, 'b'),
                RequestObjectData::asRequest('test', null, 'c'),
            ];

            // Act
            $responses = $client->addMany($requests)->request();

            // Assert
            expect($responses)->toBeInstanceOf(DataCollection::class);

            foreach ($responses as $index => $response) {
                expect($response)->toBeInstanceOf(ResponseData::class);
                expect($response->result['order'])->toBe($index + 1);
            }
        });

        test('parses single response correctly using from method', function (): void {
            // Arrange
            Http::fake([
                '*' => Http::response([
                    'jsonrpc' => '2.0',
                    'id' => 99,
                    'result' => ['parsed' => 'correctly'],
                ], 200),
            ]);

            $client = Client::create('https://api.example.com');
            $request = RequestObjectData::asRequest('parse.test', null, 99);

            // Act
            $response = $client->add($request)->request();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->id)->toBe(99);
            expect($response->jsonrpc)->toBe('2.0');
            expect($response->result)->toBe(['parsed' => 'correctly']);
        });
    });
});
