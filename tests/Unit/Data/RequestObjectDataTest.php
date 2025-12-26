<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\RequestObjectData;
use Symfony\Component\Uid\Ulid;

describe('RequestObjectData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates request object with all parameters', function (): void {
            // Arrange
            $jsonrpc = '2.0';
            $id = 'test-123';
            $method = 'user.create';
            $params = ['name' => 'John Doe', 'email' => 'john@example.com'];

            // Act
            $request = new RequestObjectData($jsonrpc, $id, $method, $params);

            // Assert
            expect($request)->toBeInstanceOf(RequestObjectData::class)
                ->and($request->jsonrpc)->toBe('2.0')
                ->and($request->id)->toBe('test-123')
                ->and($request->method)->toBe('user.create')
                ->and($request->params)->toBe(['name' => 'John Doe', 'email' => 'john@example.com']);
        });

        test('creates request using asRequest factory with auto-generated ID', function (): void {
            // Arrange
            $method = 'account.getBalance';
            $params = ['account_id' => 12_345];

            // Act
            $request = RequestObjectData::asRequest($method, $params);

            // Assert
            expect($request)->toBeInstanceOf(RequestObjectData::class)
                ->and($request->jsonrpc)->toBe('2.0')
                ->and($request->id)->toBeInstanceOf(Ulid::class)
                ->and((string) $request->id)->toMatch('/^[0-9A-Z]{26}$/i') // ULID pattern when cast to string
                ->and($request->method)->toBe('account.getBalance')
                ->and($request->params)->toBe(['account_id' => 12_345]);
        });

        test('creates request using asRequest factory with custom ID', function (): void {
            // Arrange
            $method = 'product.update';
            $params = ['product_id' => 999, 'price' => 29.99];
            $customId = 'custom-request-456';

            // Act
            $request = RequestObjectData::asRequest($method, $params, $customId);

            // Assert
            expect($request)->toBeInstanceOf(RequestObjectData::class)
                ->and($request->jsonrpc)->toBe('2.0')
                ->and($request->id)->toBe('custom-request-456')
                ->and($request->method)->toBe('product.update')
                ->and($request->params)->toBe(['product_id' => 999, 'price' => 29.99]);
        });

        test('creates notification using asNotification factory', function (): void {
            // Arrange
            $method = 'log.write';
            $params = ['level' => 'info', 'message' => 'User logged in'];

            // Act
            $notification = RequestObjectData::asNotification($method, $params);

            // Assert
            expect($notification)->toBeInstanceOf(RequestObjectData::class)
                ->and($notification->jsonrpc)->toBe('2.0')
                ->and($notification->id)->toBeNull()
                ->and($notification->method)->toBe('log.write')
                ->and($notification->params)->toBe(['level' => 'info', 'message' => 'User logged in']);
        });

        test('retrieves specific parameter using dot notation', function (): void {
            // Arrange
            $params = [
                'user' => [
                    'profile' => [
                        'email' => 'test@example.com',
                        'age' => 25,
                    ],
                ],
            ];
            $request = new RequestObjectData('2.0', 1, 'test', $params);

            // Act
            $email = $request->getParam('user.profile.email');
            $age = $request->getParam('user.profile.age');

            // Assert
            expect($email)->toBe('test@example.com')
                ->and($age)->toBe(25);
        });

        test('retrieves all parameters using getParams method', function (): void {
            // Arrange
            $params = ['key1' => 'value1', 'key2' => 'value2', 'key3' => ['nested' => 'value']];
            $request = new RequestObjectData('2.0', 'req-001', 'test.method', $params);

            // Act
            $retrievedParams = $request->getParams();

            // Assert
            expect($retrievedParams)->toBe($params)
                ->and($retrievedParams)->toHaveCount(3)
                ->and($retrievedParams['key3']['nested'])->toBe('value');
        });

        test('correctly identifies a notification request', function (): void {
            // Arrange
            $notification = new RequestObjectData('2.0', null, 'notify.event', ['event' => 'user.login']);

            // Act
            $isNotification = $notification->isNotification();

            // Assert
            expect($isNotification)->toBeTrue();
        });

        test('correctly identifies a non-notification request', function (): void {
            // Arrange
            $request = new RequestObjectData('2.0', 'req-123', 'method.call', null);

            // Act
            $isNotification = $request->isNotification();

            // Assert
            expect($isNotification)->toBeFalse();
        });

        test('creates from array using inherited from method', function (): void {
            // Arrange
            $data = [
                'jsonrpc' => '2.0',
                'id' => 'array-id',
                'method' => 'array.method',
                'params' => ['param1' => 'value1'],
            ];

            // Act
            $request = RequestObjectData::from($data);

            // Assert
            expect($request)->toBeInstanceOf(RequestObjectData::class)
                ->and($request->jsonrpc)->toBe('2.0')
                ->and($request->id)->toBe('array-id')
                ->and($request->method)->toBe('array.method')
                ->and($request->params)->toBe(['param1' => 'value1']);
        });
    });

    describe('Sad Paths', function (): void {
        test('returns default value when getting parameter from null params', function (): void {
            // Arrange
            $request = new RequestObjectData('2.0', 1, 'test', null);
            $defaultValue = 'default';

            // Act
            $result = $request->getParam('any.key', $defaultValue);

            // Assert
            expect($result)->toBe('default');
        });

        test('returns default value when parameter does not exist', function (): void {
            // Arrange
            $params = ['existing' => 'value'];
            $request = new RequestObjectData('2.0', 1, 'test', $params);

            // Act
            $result = $request->getParam('non.existent.key', 'fallback');

            // Assert
            expect($result)->toBe('fallback');
        });

        test('returns null when getting params that are null', function (): void {
            // Arrange
            $request = new RequestObjectData('2.0', 'id-null-params', 'test.null', null);

            // Act
            $params = $request->getParams();

            // Assert
            expect($params)->toBeNull();
        });

        test('returns null as default when parameter not found and no default provided', function (): void {
            // Arrange
            $request = new RequestObjectData('2.0', 1, 'test', ['key' => 'value']);

            // Act
            $result = $request->getParam('missing.key');

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('Edge Cases', function (): void {
        test('handles empty params array', function (): void {
            // Arrange
            $emptyParams = [];
            $request = new RequestObjectData('2.0', 'empty-params', 'test', $emptyParams);

            // Act
            $params = $request->getParams();
            $paramValue = $request->getParam('any.key', 'default');

            // Assert
            expect($params)->toBe([])
                ->and($params)->toBeEmpty()
                ->and($paramValue)->toBe('default');
        });

        test('handles deeply nested parameter access', function (): void {
            // Arrange
            $params = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'deep' => 'value',
                            ],
                        ],
                    ],
                ],
            ];
            $request = new RequestObjectData('2.0', 1, 'test', $params);

            // Act
            $deepValue = $request->getParam('level1.level2.level3.level4.deep');

            // Assert
            expect($deepValue)->toBe('value');
        });

        test('handles numeric ID values', function (): void {
            // Arrange
            $numericId = 12_345;

            // Act
            $request = RequestObjectData::asRequest('test', null, $numericId);

            // Assert
            expect($request->id)->toBe(12_345)
                ->and($request->id)->toBeInt();
        });

        test('handles float ID values', function (): void {
            // Arrange
            $floatId = 123.45;

            // Act
            $request = RequestObjectData::asRequest('test', null, $floatId);

            // Assert
            expect($request->id)->toBe(123.45)
                ->and($request->id)->toBeFloat();
        });

        test('handles method names with special characters', function (): void {
            // Arrange
            $methodWithDots = 'namespace.sub.method_name-123';

            // Act
            $request = RequestObjectData::asRequest($methodWithDots);

            // Assert
            expect($request->method)->toBe('namespace.sub.method_name-123');
        });

        test('handles complex data structures in params', function (): void {
            // Arrange
            $complexParams = [
                'array' => [1, 2, 3],
                'object' => ['key' => 'value'],
                'mixed' => [
                    'string' => 'text',
                    'number' => 42,
                    'float' => 3.14,
                    'bool' => true,
                    'null' => null,
                ],
            ];
            $request = new RequestObjectData('2.0', 1, 'test', $complexParams);

            // Act
            $allParams = $request->getParams();
            $arrayParam = $request->getParam('array');
            $mixedBool = $request->getParam('mixed.bool');

            // Assert
            expect($allParams)->toBe($complexParams)
                ->and($arrayParam)->toBe([1, 2, 3])
                ->and($mixedBool)->toBeTrue();
        });

        test('handles numeric string keys in params', function (): void {
            // Arrange
            $params = [
                '0' => 'first',
                '1' => 'second',
                'normal' => 'value',
            ];
            $request = new RequestObjectData('2.0', 1, 'test', $params);

            // Act
            $first = $request->getParam('0');
            $second = $request->getParam('1');
            $normal = $request->getParam('normal');

            // Assert
            expect($first)->toBe('first')
                ->and($second)->toBe('second')
                ->and($normal)->toBe('value');
        });
    });

    describe('Data Serialization', function (): void {
        test('removes null values when converting to array', function (): void {
            // Arrange
            $request = new RequestObjectData('2.0', 'id-123', 'test.method', null);

            // Act
            $array = $request->toArray();

            // Assert
            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 'id-123',
                'method' => 'test.method',
            ])
                ->and($array)->not->toHaveKey('params');
        });

        test('preserves non-null params when converting to array', function (): void {
            // Arrange
            $params = ['key' => 'value', 'nested' => ['inner' => 'data']];
            $request = new RequestObjectData('2.0', 123, 'method.name', $params);

            // Act
            $array = $request->toArray();

            // Assert
            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 123,
                'method' => 'method.name',
                'params' => ['key' => 'value', 'nested' => ['inner' => 'data']],
            ]);
        });

        test('removes null ID for notifications when converting to array', function (): void {
            // Arrange
            $notification = RequestObjectData::asNotification('notify', ['data' => 'value']);

            // Act
            $array = $notification->toArray();

            // Assert
            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'method' => 'notify',
                'params' => ['data' => 'value'],
            ])
                ->and($array)->not->toHaveKey('id');
        });

        test('jsonSerialize method returns same as toArray', function (): void {
            // Arrange
            $request = new RequestObjectData('2.0', 'json-id', 'json.method', ['json' => 'param']);

            // Act
            $array = $request->toArray();
            $json = $request->jsonSerialize();

            // Assert
            expect($json)->toBe($array);
        });

        test('handles JSON encoding correctly', function (): void {
            // Arrange
            $request = RequestObjectData::asRequest('encode.test', ['unicode' => '你好', 'emoji' => '🎉']);

            // Act
            $json = json_encode($request);
            $decoded = json_decode($json, true);

            // Assert
            expect($decoded)->toHaveKey('jsonrpc')
                ->and($decoded['jsonrpc'])->toBe('2.0')
                ->and($decoded)->toHaveKey('id')
                ->and($decoded)->toHaveKey('method')
                ->and($decoded['method'])->toBe('encode.test')
                ->and($decoded)->toHaveKey('params')
                ->and($decoded['params']['unicode'])->toBe('你好')
                ->and($decoded['params']['emoji'])->toBe('🎉');
        });
    });
});
