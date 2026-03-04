<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\OpenRpc\ValueObject\ContentDescriptorValue;
use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Methods\AbstractMethod;
use Cline\RPC\Methods\Concerns\InteractsWithAuthentication;
use Cline\RPC\Methods\Concerns\InteractsWithQueryBuilder;
use Cline\RPC\Methods\Concerns\InteractsWithTransformer;

/**
 * Concrete implementation of AbstractMethod for testing purposes.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ConcreteTestMethod extends AbstractMethod
{
    public function handle(): mixed
    {
        return ['test' => 'result'];
    }
}

/**
 * Custom method with overridden getName for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CustomNamedMethod extends AbstractMethod
{
    #[Override()]
    public function getName(): string
    {
        return 'custom.method.name';
    }

    public function handle(): mixed
    {
        return ['custom' => 'result'];
    }
}

/**
 * Method with overridden summary for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CustomSummaryMethod extends AbstractMethod
{
    #[Override()]
    public function getSummary(): string
    {
        return 'This is a custom summary for testing purposes';
    }

    public function handle(): mixed
    {
        return ['summary' => 'test'];
    }
}

/**
 * Method with custom parameters for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodWithParams extends AbstractMethod
{
    #[Override()]
    public function getParams(): array
    {
        return [
            ContentDescriptorValue::from([
                'name' => 'userId',
                'schema' => ['type' => 'integer'],
            ]),
            ContentDescriptorValue::from([
                'name' => 'email',
                'schema' => ['type' => 'string'],
            ]),
        ];
    }

    public function handle(): mixed
    {
        return ['params' => 'test'];
    }
}

/**
 * Method with custom result descriptor for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodWithResult extends AbstractMethod
{
    public function getResult(): ContentDescriptorValue
    {
        return ContentDescriptorValue::from([
            'name' => 'UserData',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                ],
            ],
        ]);
    }

    public function handle(): mixed
    {
        return ['result' => 'test'];
    }
}

/**
 * Method with custom errors for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodWithErrors extends AbstractMethod
{
    #[Override()]
    public function getErrors(): array
    {
        return [
            ['code' => -32_001, 'message' => 'User not found'],
            ['code' => -32_002, 'message' => 'Invalid permissions'],
        ];
    }

    public function handle(): mixed
    {
        return ['errors' => 'test'];
    }
}

/**
 * Method with multi-word class name for snake_case testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UserProfileSettings extends AbstractMethod
{
    public function handle(): mixed
    {
        return ['profile' => 'settings'];
    }
}

/**
 * Method with single word for snake_case testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Dashboard extends AbstractMethod
{
    public function handle(): mixed
    {
        return ['dashboard' => 'data'];
    }
}

describe('AbstractMethod', function (): void {
    describe('Happy Paths', function (): void {
        describe('getName()', function (): void {
            test('generates method name from class name in snake_case format', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBe('app.concrete_test_method');
            });

            test('converts multi-word class names to snake_case correctly', function (): void {
                // Arrange
                $method = new UserProfileSettings();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBe('app.user_profile_settings');
            });

            test('handles single-word class names correctly', function (): void {
                // Arrange
                $method = new Dashboard();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBe('app.dashboard');
            });

            test('can be overridden to provide custom method names', function (): void {
                // Arrange
                $method = new CustomNamedMethod();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBe('custom.method.name');
            });

            test('always prefixes auto-generated names with app namespace', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toStartWith('app.');
            });
        });

        describe('getSummary()', function (): void {
            test('returns method name by default', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getSummary();

                // Assert
                expect($result)->toBe('app.concrete_test_method')
                    ->and($result)->toBe($method->getName());
            });

            test('can be overridden to provide custom summaries', function (): void {
                // Arrange
                $method = new CustomSummaryMethod();

                // Act
                $result = $method->getSummary();

                // Assert
                expect($result)->toBe('This is a custom summary for testing purposes')
                    ->and($result)->not()->toBe($method->getName());
            });

            test('returns string type summary', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getSummary();

                // Assert
                expect($result)->toBeString();
            });
        });

        describe('getParams()', function (): void {
            test('returns empty array by default', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getParams();

                // Assert
                expect($result)->toBeArray()
                    ->and($result)->toBeEmpty();
            });

            test('can be overridden to define method parameters', function (): void {
                // Arrange
                $method = new MethodWithParams();

                // Act
                $result = $method->getParams();

                // Assert
                expect($result)->toBeArray()
                    ->and($result)->toHaveCount(2)
                    ->and($result[0])->toBeInstanceOf(ContentDescriptorValue::class)
                    ->and($result[1])->toBeInstanceOf(ContentDescriptorValue::class);
            });

            test('returns ContentDescriptor instances for OpenRPC compatibility', function (): void {
                // Arrange
                $method = new MethodWithParams();

                // Act
                $result = $method->getParams();

                // Assert
                expect($result[0]->name)->toBe('userId')
                    ->and($result[0]->schema)->toBe(['type' => 'integer'])
                    ->and($result[1]->name)->toBe('email')
                    ->and($result[1]->schema)->toBe(['type' => 'string']);
            });
        });

        describe('getResult()', function (): void {
            test('returns null by default', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getResult();

                // Assert
                expect($result)->toBeNull();
            });

            test('can be overridden to define result descriptor', function (): void {
                // Arrange
                $method = new MethodWithResult();

                // Act
                $result = $method->getResult();

                // Assert
                expect($result)->toBeInstanceOf(ContentDescriptorValue::class)
                    ->and($result)->not()->toBeNull();
            });

            test('returns ContentDescriptorValue with schema definition', function (): void {
                // Arrange
                $method = new MethodWithResult();

                // Act
                $result = $method->getResult();

                // Assert
                expect($result->name)->toBe('UserData')
                    ->and($result->schema)->toBeArray()
                    ->and($result->schema)->toHaveKey('type')
                    ->and($result->schema['type'])->toBe('object')
                    ->and($result->schema)->toHaveKey('properties');
            });
        });

        describe('getErrors()', function (): void {
            test('returns empty array by default', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getErrors();

                // Assert
                expect($result)->toBeArray()
                    ->and($result)->toBeEmpty();
            });

            test('can be overridden to define error descriptors', function (): void {
                // Arrange
                $method = new MethodWithErrors();

                // Act
                $result = $method->getErrors();

                // Assert
                expect($result)->toBeArray()
                    ->and($result)->toHaveCount(2);
            });

            test('returns error arrays for OpenRPC compatibility', function (): void {
                // Arrange
                $method = new MethodWithErrors();

                // Act
                $result = $method->getErrors();

                // Assert
                expect($result[0])->toBeArray()
                    ->and($result[0]['code'])->toBe(-32_001)
                    ->and($result[0]['message'])->toBe('User not found')
                    ->and($result[1])->toBeArray()
                    ->and($result[1]['code'])->toBe(-32_002)
                    ->and($result[1]['message'])->toBe('Invalid permissions');
            });
        });

        describe('setRequest()', function (): void {
            test('stores request object for later access', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'id' => '123',
                    'method' => 'test.method',
                    'params' => ['key' => 'value'],
                ]);

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest)->toBe($requestObject)
                    ->and($storedRequest->method)->toBe('test.method')
                    ->and($storedRequest->params)->toBe(['key' => 'value']);
            });

            test('accepts request with null params', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'id' => 'abc-123',
                    'method' => 'test.noParams',
                    'params' => null,
                ]);

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest->params)->toBeNull();
            });

            test('accepts request with nested params structure', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'id' => '456',
                    'method' => 'test.nested',
                    'params' => [
                        'user' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'settings' => [
                                'theme' => 'dark',
                            ],
                        ],
                    ],
                ]);

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest->params)->toHaveKey('user')
                    ->and($storedRequest->params['user'])->toHaveKey('settings');
            });
        });

        describe('MethodInterface implementation', function (): void {
            test('implements MethodInterface contract', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect($method)->toBeInstanceOf(MethodInterface::class);
            });

            test('provides all required MethodInterface methods', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'getName'))->toBeTrue()
                    ->and(method_exists($method, 'getSummary'))->toBeTrue()
                    ->and(method_exists($method, 'getParams'))->toBeTrue()
                    ->and(method_exists($method, 'getResult'))->toBeTrue()
                    ->and(method_exists($method, 'getErrors'))->toBeTrue()
                    ->and(method_exists($method, 'setRequest'))->toBeTrue()
                    ->and(method_exists($method, 'handle'))->toBeTrue();
            });
        });
    });

    describe('Sad Paths', function (): void {
        describe('getName() error handling', function (): void {
            test('handles empty class name suffix gracefully', function (): void {
                // Arrange - even with unusual naming, should still work
                $method = new ConcreteTestMethod();

                // Act
                $result = $method->getName();

                // Assert - should not throw exception
                expect($result)->toBeString()
                    ->and($result)->not()->toBeEmpty();
            });
        });

        describe('setRequest() validation', function (): void {
            test('allows overwriting previous request object', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $firstRequest = RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'id' => '1',
                    'method' => 'first.method',
                    'params' => null,
                ]);
                $secondRequest = RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'id' => '2',
                    'method' => 'second.method',
                    'params' => null,
                ]);

                // Act
                $method->setRequest($firstRequest);
                $method->setRequest($secondRequest); // Overwrite

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest)->toBe($secondRequest)
                    ->and($storedRequest->method)->toBe('second.method');
            });
        });
    });

    describe('Edge Cases', function (): void {
        describe('getName() with special characters', function (): void {
            test('handles uppercase acronyms in class names', function (): void {
                // Arrange
                /**
                 * @author Brian Faust <brian@cline.sh>
                 */
                final class JSONAPIMethod extends AbstractMethod
                {
                    public function handle(): mixed
                    {
                        return [];
                    }
                }

                $method = new JSONAPIMethod();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBe('app.j_s_o_n_a_p_i_method')
                    ->and($result)->toContain('_');
            });

            test('handles consecutive uppercase letters', function (): void {
                // Arrange
                /**
                 * @author Brian Faust <brian@cline.sh>
                 */
                final class HTTPSConnection extends AbstractMethod
                {
                    public function handle(): mixed
                    {
                        return [];
                    }
                }

                $method = new HTTPSConnection();

                // Act
                $result = $method->getName();

                // Assert
                expect($result)->toBeString()
                    ->and($result)->toStartWith('app.');
            });
        });

        describe('getParams() edge cases', function (): void {
            test('handles empty params array definition', function (): void {
                // Arrange
                /**
                 * @author Brian Faust <brian@cline.sh>
                 */
                final class EmptyParamsMethod extends AbstractMethod
                {
                    #[Override()]
                    public function getParams(): array
                    {
                        return [];
                    }

                    public function handle(): mixed
                    {
                        return [];
                    }
                }

                $method = new EmptyParamsMethod();

                // Act
                $result = $method->getParams();

                // Assert
                expect($result)->toBeArray()
                    ->and($result)->toBeEmpty();
            });

            test('handles large number of parameters', function (): void {
                // Arrange
                /**
                 * @author Brian Faust <brian@cline.sh>
                 */
                final class ManyParamsMethod extends AbstractMethod
                {
                    #[Override()]
                    public function getParams(): array
                    {
                        $params = [];

                        for ($i = 1; $i <= 20; ++$i) {
                            $params[] = ContentDescriptorValue::from([
                                'name' => 'param'.$i,
                                'schema' => ['type' => 'string'],
                            ]);
                        }

                        return $params;
                    }

                    public function handle(): mixed
                    {
                        return [];
                    }
                }

                $method = new ManyParamsMethod();

                // Act
                $result = $method->getParams();

                // Assert
                expect($result)->toHaveCount(20)
                    ->and($result[0]->name)->toBe('param1')
                    ->and($result[19]->name)->toBe('param20');
            });
        });

        describe('getErrors() edge cases', function (): void {
            test('handles single error definition', function (): void {
                // Arrange
                /**
                 * @author Brian Faust <brian@cline.sh>
                 */
                final class SingleErrorMethod extends AbstractMethod
                {
                    #[Override()]
                    public function getErrors(): array
                    {
                        return [
                            ['code' => -32_000, 'message' => 'Server error'],
                        ];
                    }

                    public function handle(): mixed
                    {
                        return [];
                    }
                }

                $method = new SingleErrorMethod();

                // Act
                $result = $method->getErrors();

                // Assert
                expect($result)->toHaveCount(1)
                    ->and($result[0]['code'])->toBe(-32_000);
            });

            test('handles many error definitions', function (): void {
                // Arrange
                /**
                 * @author Brian Faust <brian@cline.sh>
                 */
                final class ManyErrorsMethod extends AbstractMethod
                {
                    #[Override()]
                    public function getErrors(): array
                    {
                        $errors = [];

                        for ($i = 1; $i <= 10; ++$i) {
                            $errors[] = ['code' => -32_000 - $i, 'message' => 'Error '.$i];
                        }

                        return $errors;
                    }

                    public function handle(): mixed
                    {
                        return [];
                    }
                }

                $method = new ManyErrorsMethod();

                // Act
                $result = $method->getErrors();

                // Assert
                expect($result)->toHaveCount(10)
                    ->and($result[0]['code'])->toBe(-32_001)
                    ->and($result[9]['code'])->toBe(-32_010);
            });
        });

        describe('setRequest() with various request types', function (): void {
            test('handles notification request without id', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::asNotification('test.notification');

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest->id)->toBeNull()
                    ->and($storedRequest->isNotification())->toBeTrue();
            });

            test('handles request with string id', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::asRequest('test.method', null, 'string-id-123');

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest->id)->toBe('string-id-123')
                    ->and($storedRequest->id)->toBeString();
            });

            test('handles request with numeric id', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::from([
                    'jsonrpc' => '2.0',
                    'id' => 42,
                    'method' => 'test.method',
                    'params' => null,
                ]);

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest->id)->toBe(42)
                    ->and($storedRequest->id)->toBeInt();
            });

            test('handles request with complex params array', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $complexParams = [
                    'filter' => [
                        'status' => ['active', 'pending'],
                        'created_at' => ['gte' => '2024-01-01'],
                    ],
                    'sort' => ['-created_at', 'name'],
                    'include' => ['author', 'comments'],
                    'fields' => [
                        'users' => ['id', 'name', 'email'],
                        'posts' => ['id', 'title'],
                    ],
                ];
                $requestObject = RequestObjectData::asRequest('test.complex', $complexParams);

                // Act
                $method->setRequest($requestObject);

                // Assert
                $reflection = new ReflectionClass($method);
                $property = $reflection->getProperty('requestObject');

                $storedRequest = $property->getValue($method);

                expect($storedRequest->params)->toBe($complexParams)
                    ->and($storedRequest->getParam('filter.status'))->toBe(['active', 'pending'])
                    ->and($storedRequest->getParam('sort'))->toBe(['-created_at', 'name']);
            });
        });

        describe('method chaining scenarios', function (): void {
            test('allows setting request before calling getName', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $requestObject = RequestObjectData::asRequest('test.method');

                // Act
                $method->setRequest($requestObject);
                $name = $method->getName();

                // Assert
                expect($name)->toBe('app.concrete_test_method');
            });

            test('getName remains consistent after setRequest', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $nameBefore = $method->getName();

                $requestObject = RequestObjectData::asRequest('different.method');
                $method->setRequest($requestObject);

                // Act
                $nameAfter = $method->getName();

                // Assert
                expect($nameAfter)->toBe($nameBefore)
                    ->and($nameAfter)->toBe('app.concrete_test_method');
            });
        });
    });

    describe('Trait Integration', function (): void {
        describe('InteractsWithAuthentication trait', function (): void {
            test('includes InteractsWithAuthentication trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $traits = class_uses_recursive($method);

                // Assert
                expect($traits)->toContain(InteractsWithAuthentication::class);
            });

            test('provides getCurrentUser method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'getCurrentUser'))->toBeTrue();
            });
        });

        describe('InteractsWithQueryBuilder trait', function (): void {
            test('includes InteractsWithQueryBuilder trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $traits = class_uses_recursive($method);

                // Assert
                expect($traits)->toContain(InteractsWithQueryBuilder::class);
            });

            test('provides query method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'query'))->toBeTrue();
            });
        });

        describe('InteractsWithTransformer trait', function (): void {
            test('includes InteractsWithTransformer trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $traits = class_uses_recursive($method);

                // Assert
                expect($traits)->toContain(InteractsWithTransformer::class);
            });

            test('provides item method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'item'))->toBeTrue();
            });

            test('provides collection method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'collection'))->toBeTrue();
            });

            test('provides cursorPaginate method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'cursorPaginate'))->toBeTrue();
            });

            test('provides paginate method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'paginate'))->toBeTrue();
            });

            test('provides simplePaginate method from trait', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act & Assert
                expect(method_exists($method, 'simplePaginate'))->toBeTrue();
            });
        });

        describe('all three traits work together', function (): void {
            test('method has access to all trait methods', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();
                $expectedMethods = [
                    // From InteractsWithAuthentication
                    'getCurrentUser',
                    // From InteractsWithQueryBuilder
                    'query',
                    // From InteractsWithTransformer
                    'item',
                    'collection',
                    'cursorPaginate',
                    'paginate',
                    'simplePaginate',
                ];

                // Act & Assert
                foreach ($expectedMethods as $methodName) {
                    expect(method_exists($method, $methodName))->toBeTrue();
                }
            });

            test('all three traits are present in class hierarchy', function (): void {
                // Arrange
                $method = new ConcreteTestMethod();

                // Act
                $traits = class_uses_recursive($method);

                // Assert
                expect($traits)->toHaveCount(3)
                    ->and($traits)->toContain(InteractsWithAuthentication::class)
                    ->and($traits)->toContain(InteractsWithQueryBuilder::class)
                    ->and($traits)->toContain(InteractsWithTransformer::class);
            });
        });
    });
});
