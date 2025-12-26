<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\OpenRpc\ValueObject\ContentDescriptorValue;
use Cline\OpenRpc\ValueObject\SchemaValue;
use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Facades\Server as ServerFacade;
use Cline\RPC\Methods\DiscoverMethod;
use Cline\RPC\Repositories\MethodRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Mockery\MockInterface;

describe('DiscoverMethod', function (): void {
    beforeEach(function (): void {
        // Create a mock ServerInterface and swap the facade
        $mockServer = Mockery::mock(ServerInterface::class);
        ServerFacade::swap($mockServer);
    });

    describe('Happy Paths', function (): void {
        describe('getName()', function (): void {
            test('returns standard OpenRPC discovery method name', function (): void {
                // Arrange
                $method = new DiscoverMethod();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBe('rpc.discover');
            });
        });

        describe('getSummary()', function (): void {
            test('returns description of discovery method', function (): void {
                // Arrange
                $method = new DiscoverMethod();

                // Act
                $result = $method->getSummary();

                // Assert
                expect($result)->toBe('Returns an OpenRPC schema as a description of this service');
            });
        });

        describe('getResult()', function (): void {
            test('returns ContentDescriptorValue with OpenRPC schema reference', function (): void {
                // Arrange
                $method = new DiscoverMethod();

                // Act
                $result = $method->getResult();

                // Assert
                expect($result)->toBeInstanceOf(ContentDescriptorValue::class);

                $array = $result->toArray();
                expect($array)->toHaveKey('name')
                    ->and($array['name'])->toBe('OpenRPC Schema')
                    ->and($array)->toHaveKey('schema')
                    ->and($array['schema'])->toHaveKey('$ref')
                    ->and($array['schema']['$ref'])->toBe('https://raw.githubusercontent.com/open-rpc/meta-schema/master/schema.json');
            });
        });

        describe('handle()', function (): void {
            test('generates complete OpenRPC 1.3.2 schema document', function (): void {
                // Arrange
                $mockMethod = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('test.method');
                    $mock->shouldReceive('getSummary')->andReturn('Test method summary');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([]);
                });

                $repository = new MethodRepository([$mockMethod]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result)->toBeArray()
                    ->and($result)->toHaveKey('openrpc')
                    ->and($result['openrpc'])->toBe('1.3.2')
                    ->and($result)->toHaveKey('info')
                    ->and($result['info'])->toHaveKey('title')
                    ->and($result['info'])->toHaveKey('version')
                    ->and($result['info'])->toHaveKey('license')
                    ->and($result)->toHaveKey('servers')
                    ->and($result)->toHaveKey('methods')
                    ->and($result)->toHaveKey('components');
            });

            test('includes server configuration with environment and URL', function (): void {
                // Arrange
                $repository = new MethodRepository([]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Production Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('2.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/api/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('production');
                URL::shouldReceive('to')->with('/api/rpc')->andReturn('https://example.com/api/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['servers'])->toBeArray()
                    ->and($result['servers'])->toHaveCount(1)
                    ->and($result['servers'][0])->toHaveKey('name')
                    ->and($result['servers'][0]['name'])->toBe('production')
                    ->and($result['servers'][0])->toHaveKey('url')
                    ->and($result['servers'][0]['url'])->toBe('https://example.com/api/rpc');
            });

            test('aggregates errors from multiple sources', function (): void {
                // Arrange
                $mockMethod1 = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('method.one');
                    $mock->shouldReceive('getSummary')->andReturn('Method One');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([
                        ['code' => -32_001, 'message' => 'Custom error 1'],
                    ]);
                });

                $mockMethod2 = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('method.two');
                    $mock->shouldReceive('getSummary')->andReturn('Method Two');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([
                        ['code' => -32_002, 'message' => 'Custom error 2'],
                    ]);
                });

                $repository = new MethodRepository([$mockMethod1, $mockMethod2]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['methods'])->toHaveCount(2)
                    ->and($result['methods'][0]['errors'])->toBeArray()
                    ->and($result['methods'][0]['errors'])->toContain(['code' => -32_001, 'message' => 'Custom error 1', 'data' => null])
                    ->and($result['methods'][1]['errors'])->toContain(['code' => -32_002, 'message' => 'Custom error 2', 'data' => null]);
            });

            test('assembles components section with contentDescriptors, schemas, and errors', function (): void {
                // Arrange
                $contentDescriptor = ContentDescriptorValue::from([
                    'name' => 'TestDescriptor',
                    'schema' => ['type' => 'string'],
                ]);

                $schema = new SchemaValue('TestSchema', ['type' => 'object']);

                $repository = new MethodRepository([]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([$contentDescriptor]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([$schema]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['components'])->toHaveKey('contentDescriptors')
                    ->and($result['components'])->toHaveKey('schemas')
                    ->and($result['components'])->toHaveKey('errors')
                    ->and($result['components']['contentDescriptors'])->toHaveKey('TestDescriptor')
                    ->and($result['components']['schemas'])->toHaveKey('TestSchema')
                    ->and($result['components']['errors'])->not()->toBeEmpty();
            });

            test('includes all standard JSON-RPC error codes', function (): void {
                // Arrange
                $repository = new MethodRepository([]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                $errorMessages = array_keys($result['components']['errors']);

                expect($errorMessages)->toContain('Internal error')
                    ->and($errorMessages)->toContain('Invalid fields')
                    ->and($errorMessages)->toContain('Invalid filters')
                    ->and($errorMessages)->toContain('Invalid params')
                    ->and($errorMessages)->toContain('Invalid relationships')
                    ->and($errorMessages)->toContain('Invalid Request')
                    ->and($errorMessages)->toContain('Invalid sorts')
                    ->and($errorMessages)->toContain('Method not found')
                    ->and($errorMessages)->toContain('Parse error')
                    ->and($errorMessages)->toContain('Server error')
                    ->and($errorMessages)->toContain('Server not found');
            });

            test('collects all methods from repository', function (): void {
                // Arrange
                $mockMethod1 = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('users.list');
                    $mock->shouldReceive('getSummary')->andReturn('List all users');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([]);
                });

                $mockMethod2 = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('users.get');
                    $mock->shouldReceive('getSummary')->andReturn('Get a single user');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([]);
                });

                $repository = new MethodRepository([$mockMethod1, $mockMethod2]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['methods'])->toHaveCount(2)
                    ->and($result['methods'][0]['name'])->toBe('users.list')
                    ->and($result['methods'][0]['summary'])->toBe('List all users')
                    ->and($result['methods'][1]['name'])->toBe('users.get')
                    ->and($result['methods'][1]['summary'])->toBe('Get a single user');
            });

            test('transforms document through DocumentValue', function (): void {
                // Arrange
                $repository = new MethodRepository([]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert - DocumentValue ensures proper structure
                expect($result)->toBeArray()
                    ->and($result)->toHaveKey('openrpc')
                    ->and($result)->toHaveKey('info')
                    ->and($result)->toHaveKey('servers')
                    ->and($result)->toHaveKey('methods')
                    ->and($result)->toHaveKey('components');
            });
        });
    });

    describe('Edge Cases', function (): void {
        describe('arr_filter_recursive()', function (): void {
            test('removes null values from array', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 'value1',
                    'key2' => null,
                    'key3' => 'value3',
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe(['key1' => 'value1', 'key3' => 'value3']);
            });

            test('removes empty strings from array', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 'value1',
                    'key2' => '',
                    'key3' => 'value3',
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe(['key1' => 'value1', 'key3' => 'value3']);
            });

            test('preserves boolean false values', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 'value1',
                    'key2' => false,
                    'key3' => true,
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe([
                    'key1' => 'value1',
                    'key2' => false,
                    'key3' => true,
                ]);
            });

            test('preserves numeric zero values', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 1,
                    'key2' => 0,
                    'key3' => 2,
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe([
                    'key1' => 1,
                    'key2' => 0,
                    'key3' => 2,
                ]);
            });

            test('recursively filters nested arrays', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'level1' => [
                        'key1' => 'value1',
                        'key2' => null,
                        'level2' => [
                            'key3' => 'value3',
                            'key4' => '',
                            'key5' => false,
                        ],
                    ],
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe([
                    'level1' => [
                        'key1' => 'value1',
                        'level2' => [
                            'key3' => 'value3',
                            'key5' => false,
                        ],
                    ],
                ]);
            });

            test('removes empty arrays when removeEmptyArrays is true', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 'value1',
                    'key2' => [],
                    'key3' => [
                        'nested' => [],
                    ],
                ];

                // Act
                $result = $filterMethod->invoke(null, $input, null, true);

                // Assert
                expect($result)->toBe(['key1' => 'value1']);
            });

            test('keeps empty arrays when removeEmptyArrays is false', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 'value1',
                    'key2' => [],
                    'key3' => 'value3',
                ];

                // Act
                $result = $filterMethod->invoke(null, $input, null, false);

                // Assert
                expect($result)->toBe([
                    'key1' => 'value1',
                    'key2' => [],
                    'key3' => 'value3',
                ]);
            });

            test('uses custom callback to filter values', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'key1' => 1,
                    'key2' => 2,
                    'key3' => 3,
                    'key4' => 4,
                ];

                $callback = fn ($value): bool => $value % 2 === 0; // Keep even numbers only

                // Act
                $result = $filterMethod->invoke(null, $input, $callback);

                // Assert
                expect($result)->toBe([
                    'key2' => 2,
                    'key4' => 4,
                ]);
            });

            test('custom callback works with nested arrays', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'level1' => [
                        'key1' => 1,
                        'key2' => 2,
                        'nested' => [
                            'key3' => 3,
                            'key4' => 4,
                        ],
                    ],
                ];

                $callback = fn ($value): bool => $value % 2 === 0;

                // Act
                $result = $filterMethod->invoke(null, $input, $callback);

                // Assert
                expect($result)->toBe([
                    'level1' => [
                        'key2' => 2,
                        'nested' => [
                            'key4' => 4,
                        ],
                    ],
                ]);
            });

            test('handles deeply nested arrays correctly', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'l1' => [
                        'l2' => [
                            'l3' => [
                                'key1' => 'value1',
                                'key2' => null,
                                'key3' => false,
                            ],
                        ],
                    ],
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe([
                    'l1' => [
                        'l2' => [
                            'l3' => [
                                'key1' => 'value1',
                                'key3' => false,
                            ],
                        ],
                    ],
                ]);
            });

            test('handles mixed types correctly', function (): void {
                // Arrange
                $method = new DiscoverMethod();
                $reflection = new ReflectionClass($method);
                $filterMethod = $reflection->getMethod('arr_filter_recursive');

                $input = [
                    'string' => 'value',
                    'null' => null,
                    'false' => false,
                    'zero' => 0,
                    'empty' => '',
                    'array' => ['nested' => 'value'],
                    'true' => true,
                    'number' => 42,
                ];

                // Act
                $result = $filterMethod->invoke(null, $input);

                // Assert
                expect($result)->toBe([
                    'string' => 'value',
                    'false' => false,
                    'zero' => 0,
                    'array' => ['nested' => 'value'],
                    'true' => true,
                    'number' => 42,
                ]);
            });
        });

        describe('handle() with empty data', function (): void {
            test('handles server with no registered methods', function (): void {
                // Arrange
                $repository = new MethodRepository([]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Empty Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('0.0.1');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['methods'])->toBeArray()
                    ->and($result['methods'])->toHaveCount(0);
            });

            test('handles empty content descriptors and schemas', function (): void {
                // Arrange
                $repository = new MethodRepository([]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['components']['contentDescriptors'])->toBeArray()
                    ->and($result['components']['schemas'])->toBeArray();
            });
        });
    });

    describe('Sad Paths', function (): void {
        describe('handle() error scenarios', function (): void {
            test('handles method with null result gracefully', function (): void {
                // Arrange
                $mockMethod = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('test.method');
                    $mock->shouldReceive('getSummary')->andReturn('Test');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([]);
                });

                $repository = new MethodRepository([$mockMethod]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert - should not throw exception
                expect($result)->toBeArray()
                    ->and($result['methods'][0]['result'])->toBeNull();
            });

            test('handles methods with empty error arrays', function (): void {
                // Arrange
                $mockMethod = mock(MethodInterface::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('getName')->andReturn('test.method');
                    $mock->shouldReceive('getSummary')->andReturn('Test');
                    $mock->shouldReceive('getParams')->andReturn([]);
                    $mock->shouldReceive('getResult')->andReturn(null);
                    $mock->shouldReceive('getErrors')->andReturn([]);
                });

                $repository = new MethodRepository([$mockMethod]);

                ServerFacade::shouldReceive('getMethodRepository')->andReturn($repository);
                ServerFacade::shouldReceive('getName')->andReturn('Test Server');
                ServerFacade::shouldReceive('getVersion')->andReturn('1.0.0');
                ServerFacade::shouldReceive('getRoutePath')->andReturn('/rpc');
                ServerFacade::shouldReceive('getContentDescriptors')->andReturn([]);
                ServerFacade::shouldReceive('getSchemas')->andReturn([]);

                App::shouldReceive('environment')->andReturn('testing');
                URL::shouldReceive('to')->with('/rpc')->andReturn('http://localhost/rpc');

                $method = new DiscoverMethod();

                // Act
                $result = $method->handle();

                // Assert
                expect($result['methods'][0]['errors'])->toBeArray()
                    ->and(count($result['methods'][0]['errors']))->toBeGreaterThan(0); // Should still have standard errors
            });
        });
    });
});
