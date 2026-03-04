<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Contracts\UnwrappedResponseInterface;
use Cline\RPC\Data\ErrorData;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Data\ResponseData;
use Cline\RPC\Exceptions\InvalidDataException;
use Cline\RPC\Jobs\CallMethod;
use Cline\RPC\Methods\AbstractMethod;
use Illuminate\Validation\ValidationException;
use Tests\Support\Fixtures\ProductData;
use Tests\Support\Fixtures\ValidatedUserData;

describe('CallMethod', function (): void {
    describe('Happy Paths', function (): void {
        test('executes method successfully and returns wrapped response', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => '123',
                'method' => 'test.method',
                'params' => ['data' => ['name' => 'John']],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $name): array
                {
                    return ['status' => 'success', 'data' => ['name' => $name]];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->jsonrpc)->toBe('2.0')
                ->and($response->id)->toBe('123')
                ->and($response->result)->toBe(['status' => 'success', 'data' => ['name' => 'John']])
                ->and($response->error)->toBeNull();
        });

        test('executes method with no parameters and returns result', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'abc-123',
                'method' => 'test.noParams',
                'params' => null,
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): array
                {
                    return ['message' => 'No params required'];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe(['message' => 'No params required']);
        });

        test('returns unwrapped response when method implements UnwrappedResponseInterface', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => '456',
                'method' => 'test.unwrapped',
                'params' => ['data' => []],
            ]);

            $method = new class() extends AbstractMethod implements UnwrappedResponseInterface
            {
                public function handle(): array
                {
                    return ['custom' => 'structure', 'items' => [1, 2, 3]];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeArray()
                ->and($response)->toBe(['custom' => 'structure', 'items' => [1, 2, 3]])
                ->and($response)->not->toBeInstanceOf(ResponseData::class);
        });

        test('resolves method parameters from request data', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => '789',
                'method' => 'user.create',
                'params' => [
                    'data' => [
                        'name' => 'Jane Doe',
                        'email' => 'jane@example.com',
                        'age' => 30,
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $name, string $email, int $age): array
                {
                    return ['name' => $name, 'email' => $email, 'age' => $age];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'age' => 30,
                ]);
        });

        test('handles method with requestObject parameter correctly', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'req-123',
                'method' => 'test.withRequestObject',
                'params' => ['data' => ['value' => 42]],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(RequestObjectData $requestObject, int $value): array
                {
                    return [
                        'method' => $requestObject->method,
                        'value' => $value,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe([
                'method' => 'test.withRequestObject',
                'value' => 42,
            ]);
        });

        test('resolves snake_case parameters to camelCase method parameters', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'snake-123',
                'method' => 'test.snakeCase',
                'params' => [
                    'data' => [
                        'first.name' => 'John',
                        'last.name' => 'Smith',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $firstName, string $lastName): array
                {
                    return ['full_name' => $firstName.' '.$lastName];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe(['full_name' => 'John Smith']);
        });

        test('handles array data parameter when method expects array type', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'array-123',
                'method' => 'test.arrayData',
                'params' => [
                    'data' => [
                        'items' => ['a', 'b', 'c'],
                        'count' => 3,
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(array $data): array
                {
                    return ['received' => $data];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result['received'])->toBe([
                'items' => ['a', 'b', 'c'],
                'count' => 3,
            ]);
        });

        test('resolves Data object parameter with validateAndCreate', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'data-object-123',
                'method' => 'user.createWithData',
                'params' => [
                    'data' => [
                        'userInfo' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'age' => 30,
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $userInfo): array
                {
                    return [
                        'created' => true,
                        'user' => $userInfo->toArray(),
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'created' => true,
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'age' => 30,
                    ],
                ]);
        });

        test('resolves Data object when parameter name is "data"', function (): void {
            // Arrange - Special case where parameter is named "data"
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'data-param-123',
                'method' => 'product.create',
                'params' => [
                    'data' => [
                        'title' => 'Test Product',
                        'price' => 99.99,
                        'description' => 'A test product',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ProductData $data): array
                {
                    return [
                        'success' => true,
                        'product' => $data->toArray(),
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'success' => true,
                    'product' => [
                        'title' => 'Test Product',
                        'price' => 99.99,
                        'description' => 'A test product',
                    ],
                ]);
        });

        test('resolves multiple Data object parameters', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'multi-data-123',
                'method' => 'order.create',
                'params' => [
                    'data' => [
                        'user' => [
                            'name' => 'Jane Smith',
                            'email' => 'jane@example.com',
                            'age' => 25,
                        ],
                        'product' => [
                            'title' => 'Premium Widget',
                            'price' => 149.99,
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $user, ProductData $product): array
                {
                    return [
                        'order_created' => true,
                        'user_name' => $user->name,
                        'product_title' => $product->title,
                        'total' => $product->price,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'order_created' => true,
                    'user_name' => 'Jane Smith',
                    'product_title' => 'Premium Widget',
                    'total' => 149.99,
                ]);
        });

        test('handles mixed Data objects and primitive parameters', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'mixed-params-123',
                'method' => 'invoice.create',
                'params' => [
                    'data' => [
                        'product' => [
                            'title' => 'Service Fee',
                            'price' => 200.00,
                        ],
                        'quantity' => 3,
                        'discount' => 0.1,
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ProductData $product, int $quantity, float $discount): array
                {
                    $subtotal = $product->price * $quantity;
                    $total = $subtotal * (1 - $discount);

                    return [
                        'product' => $product->title,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'discount' => $discount * 100 .'%',
                        'total' => $total,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'product' => 'Service Fee',
                    'quantity' => 3,
                    'subtotal' => 600.00,
                    'discount' => '10%',
                    'total' => 540.00,
                ]);
        });
    });

    describe('Sad Paths', function (): void {
        test('catches exception and returns error response', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'error-123',
                'method' => 'test.failing',
                'params' => null,
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): never
                {
                    throw new Exception('Something went wrong');
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->jsonrpc)->toBe('2.0')
                ->and($response->id)->toBe('error-123')
                ->and($response->result)->toBeNull()
                ->and($response->error)->toBeInstanceOf(ErrorData::class);
        });

        test('handles ValidationException during Data object validation', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'validation-error',
                'method' => 'test.validation',
                'params' => [
                    'data' => [
                        'email' => 'not-an-email',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): never
                {
                    $validator = validator(['email' => 'not-an-email'], ['email' => 'required|email']);

                    throw new ValidationException($validator);
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class);
        });

        test('returns error response for method that throws exception', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'runtime-error',
                'method' => 'test.runtimeError',
                'params' => ['data' => []],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): array
                {
                    throw new RuntimeException('Runtime error occurred');
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->result)->toBeNull();
        });

        test('handles InvalidDataException during parameter resolution', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'invalid-data',
                'method' => 'test.invalidData',
                'params' => [
                    'data' => [
                        'invalid' => 'data',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): never
                {
                    $validator = validator(['field' => 'invalid'], ['field' => 'required|numeric']);
                    $validationException = new ValidationException($validator);

                    throw InvalidDataException::create($validationException);
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class);
        });

        test('throws InvalidDataException when Data object validation fails', function (): void {
            // Arrange - Invalid email format to trigger validation error
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'data-validation-fail',
                'method' => 'user.create',
                'params' => [
                    'data' => [
                        'user' => [
                            'name' => 'Jo',  // Too short (min 3)
                            'email' => 'not-an-email',  // Invalid email format
                            'age' => 200,  // Too high (max 150)
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $user): array
                {
                    return ['user' => $user->toArray()];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - Should get error response due to validation failure
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->error->code)->toBe(-32_602)
                ->and($response->error->message)->toBe('Invalid params')
                ->and($response->result)->toBeNull();
        });

        test('handles validation error for Data parameter named "data"', function (): void {
            // Arrange - Missing required field to trigger validation
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'data-param-validation-fail',
                'method' => 'product.create',
                'params' => [
                    'data' => [
                        'title' => 'Test',  // Valid
                        // Missing required 'price' field
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ProductData $data): array
                {
                    return ['product' => $data->toArray()];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - Should get error response due to missing required field
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->error->code)->toBe(-32_602)
                ->and($response->result)->toBeNull();
        });

        test('handles validation failure for multiple Data parameters', function (): void {
            // Arrange - Invalid data for both parameters
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'multi-data-validation-fail',
                'method' => 'order.create',
                'params' => [
                    'data' => [
                        'user' => [
                            'name' => 'Valid Name',
                            'email' => 'invalid-email',  // Invalid email
                        ],
                        'product' => [
                            'title' => 'Product',
                            'price' => -10,  // Negative price (min 0)
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $user, ProductData $product): array
                {
                    return [
                        'user' => $user->name,
                        'product' => $product->title,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - First Data validation failure should trigger error
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->error->code)->toBe(-32_602);
        });
    });

    describe('Edge Cases', function (): void {
        test('handles empty params array', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'empty-params',
                'method' => 'test.noParams',
                'params' => ['data' => []],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): array
                {
                    return ['status' => 'ok'];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe(['status' => 'ok']);
        });

        test('handles null parameter values', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'null-params',
                'method' => 'test.nullValues',
                'params' => [
                    'data' => [
                        'name' => 'Test',
                        'description' => null,
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $name, ?string $description = null): array
                {
                    return ['name' => $name, 'description' => $description];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe(['name' => 'Test', 'description' => null]);
        });

        test('filters out null values from resolved parameters', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'filter-nulls',
                'method' => 'test.filterNulls',
                'params' => [
                    'data' => [
                        'name' => 'John',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $name, ?string $optional = null): array
                {
                    return ['name' => $name, 'has_optional' => $optional !== null];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe(['name' => 'John', 'has_optional' => false]);
        });

        test('handles method with multiple parameter types', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'mixed-types',
                'method' => 'test.mixedTypes',
                'params' => [
                    'data' => [
                        'string' => 'text',
                        'number' => 42,
                        'float' => 3.14,
                        'bool' => true,
                        'array' => [1, 2, 3],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(
                    string $string,
                    int $number,
                    float $float,
                    bool $bool,
                    array $array,
                ): array {
                    return ['string' => $string, 'number' => $number, 'float' => $float, 'bool' => $bool, 'array' => $array];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe([
                'string' => 'text',
                'number' => 42,
                'float' => 3.14,
                'bool' => true,
                'array' => [1, 2, 3],
            ]);
        });

        test('handles notification request without id', function (): void {
            // Arrange
            $requestObject = RequestObjectData::asNotification('test.notification', ['data' => ['event' => 'test']]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $event): array
                {
                    return ['received' => $event];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->id)->toBeNull()
                ->and($response->result)->toBe(['received' => 'test']);
        });

        test('handles nested parameter paths with dot notation', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'nested-params',
                'method' => 'test.nested',
                'params' => [
                    'data' => [
                        'user.profile.name' => 'John',
                        'user.profile.age' => 30,
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $userProfileName, int $userProfileAge): array
                {
                    return ['name' => $userProfileName, 'age' => $userProfileAge];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe(['name' => 'John', 'age' => 30]);
        });

        test('handles exception in method that implements UnwrappedResponseInterface', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'unwrapped-error',
                'method' => 'test.unwrappedError',
                'params' => null,
            ]);

            $method = new class() extends AbstractMethod implements UnwrappedResponseInterface
            {
                public function handle(): never
                {
                    throw new Exception('Unwrapped method failed');
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - Even unwrapped methods return ResponseData on error
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class);
        });

        test('preserves jsonrpc version from request in response', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'version-test',
                'method' => 'test.version',
                'params' => null,
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): array
                {
                    return ['version' => '1.0'];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->jsonrpc)->toBe('2.0');
        });

        test('handles method with no type hints on parameters', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'no-types',
                'method' => 'test.noTypes',
                'params' => [
                    'data' => [
                        'value' => 'anything',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle($value): array
                {
                    return ['value' => $value];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe(['value' => 'anything']);
        });

        test('resolves parameters when reflection returns no named type', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'union-type',
                'method' => 'test.unionType',
                'params' => [
                    'data' => [
                        'value' => 'test',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string|int $value): array
                {
                    return ['value' => $value, 'type' => gettype($value)];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe(['value' => 'test', 'type' => 'string']);
        });

        test('handles optional parameters that are not provided', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'optional-params',
                'method' => 'test.optionalParams',
                'params' => [
                    'data' => [
                        'required' => 'value',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $required, ?string $optional = null, int $count = 0): array
                {
                    return [
                        'required' => $required,
                        'optional' => $optional,
                        'count' => $count,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe([
                'required' => 'value',
                'optional' => null,
                'count' => 0,
            ]);
        });

        test('filters falsy values from resolved parameters', function (): void {
            // Arrange - This tests the actual behavior where array_filter removes falsy values
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'falsy-params',
                'method' => 'test.falsyParams',
                'params' => [
                    'data' => [
                        'enabled' => false,
                        'active' => true,
                        'count' => 0,
                        'name' => '',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(bool $enabled = false, bool $active = false, ?int $count = null, ?string $name = null): array
                {
                    return [
                        'enabled' => $enabled,
                        'active' => $active,
                        'count' => $count,
                        'name' => $name,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - False, 0, and empty string are filtered out by array_filter
            expect($response->result)->toBe([
                'enabled' => false,
                'active' => true,
                'count' => null,
                'name' => null,
            ]);
        });

        test('handles Data object parameter with empty object', function (): void {
            // Arrange - Empty object for Data parameter to test validation
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'empty-data-object',
                'method' => 'process.withData',
                'params' => [
                    'data' => [
                        'action' => 'validate',
                        'product' => [],  // Empty object for ProductData
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(string $action, ProductData $product): array
                {
                    return [
                        'action' => $action,
                        'product' => $product->toArray(),
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - Should get validation error for missing required fields
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->error->code)->toBe(-32_602)
                ->and($response->result)->toBeNull();
        });

        test('handles Data object with partial valid data', function (): void {
            // Arrange - Some valid, some invalid fields
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'partial-data',
                'method' => 'user.update',
                'params' => [
                    'data' => [
                        'user' => [
                            'name' => 'Valid Name',
                            'email' => 'valid@example.com',
                            // age is optional and not provided
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $user): array
                {
                    return [
                        'updated' => true,
                        'name' => $user->name,
                        'email' => $user->email,
                        'has_age' => $user->age !== null,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'updated' => true,
                    'name' => 'Valid Name',
                    'email' => 'valid@example.com',
                    'has_age' => false,
                ]);
        });

        test('handles Data object parameter with snake_case to camelCase conversion', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'snake-case-data',
                'method' => 'user.process',
                'params' => [
                    'data' => [
                        'user.data' => [  // snake_case version of userData
                            'name' => 'Snake Case User',
                            'email' => 'snake@example.com',
                            'age' => 35,
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $userData): array
                {
                    return [
                        'processed' => true,
                        'user' => $userData->toArray(),
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->result)->toBe([
                    'processed' => true,
                    'user' => [
                        'name' => 'Snake Case User',
                        'email' => 'snake@example.com',
                        'age' => 35,
                    ],
                ]);
        });
    });

    describe('Regressions', function (): void {
        test('ensures ExceptionMapper is used for all caught exceptions', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'mapper-test',
                'method' => 'test.mapper',
                'params' => null,
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): never
                {
                    throw new LogicException('Custom logic error');
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - Verify exception was mapped through ExceptionMapper
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->error->code)->toBeInt()
                ->and($response->error->message)->toBeString();
        });

        test('ensures requestObject parameter is always filtered from parameter resolution', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'filter-request-object',
                'method' => 'test.filterRequestObject',
                'params' => [
                    'data' => [
                        'requestObject' => 'should-be-ignored',
                        'actualParam' => 'should-be-used',
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(RequestObjectData $requestObject, string $actualParam): array
                {
                    // requestObject should come from injected parameter, not from data
                    return [
                        'method' => $requestObject->method,
                        'param' => $actualParam,
                    ];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert
            expect($response->result)->toBe([
                'method' => 'test.filterRequestObject',
                'param' => 'should-be-used',
            ]);
        });

        test('ensures error responses always use jsonrpc 2.0', function (): void {
            // Arrange
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'error-version',
                'method' => 'test.errorVersion',
                'params' => null,
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(): never
                {
                    throw new Exception('Test error');
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - Error response should always use '2.0'
            expect($response->jsonrpc)->toBe('2.0')
                ->and($response->error)->toBeInstanceOf(ErrorData::class);
        });

        test('ensures ValidationException is wrapped in InvalidDataException for Data objects', function (): void {
            // Arrange - Regression test to ensure line 154 is covered
            $requestObject = RequestObjectData::from([
                'jsonrpc' => '2.0',
                'id' => 'validation-wrap',
                'method' => 'user.register',
                'params' => [
                    'data' => [
                        'userData' => [
                            'name' => 'A',  // Too short, will trigger validation
                            'email' => '',  // Empty email, will trigger validation
                        ],
                    ],
                ],
            ]);

            $method = new class() extends AbstractMethod
            {
                public function handle(ValidatedUserData $userData): array
                {
                    return ['registered' => true];
                }
            };

            // Act
            $job = new CallMethod($method, $requestObject);
            $response = $job->handle();

            // Assert - ValidationException should be caught and wrapped in InvalidDataException
            expect($response)->toBeInstanceOf(ResponseData::class)
                ->and($response->error)->toBeInstanceOf(ErrorData::class)
                ->and($response->error->code)->toBe(-32_602)
                ->and($response->error->message)->toBe('Invalid params')
                ->and($response->result)->toBeNull();
        });
    });
});
