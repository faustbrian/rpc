<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\MethodResultData;

describe('MethodResultData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates instance from array', function (): void {
            // Arrange
            $inputData = [
                'jsonrpc' => '2.0',
                'id' => '123',
                'result' => ['status' => 'success'],
            ];

            // Act
            $data = MethodResultData::from($inputData);

            // Assert
            expect($data)->toBeInstanceOf(MethodResultData::class)
                ->and($data->jsonrpc)->toBe('2.0')
                ->and($data->id)->toBe('123')
                ->and($data->result)->toBe(['status' => 'success']);
        });

        test('handles null result', function (): void {
            // Arrange
            $inputData = [
                'jsonrpc' => '2.0',
                'id' => '456',
                'result' => null,
            ];

            // Act
            $data = MethodResultData::from($inputData);

            // Assert
            expect($data)->toBeInstanceOf(MethodResultData::class)
                ->and($data->result)->toBeNull();
        });

        test('toArray returns correct structure with string ID', function (): void {
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'test-id-123',
                result: ['data' => 'test value'],
            );

            $array = $data->toArray();

            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 'test-id-123',
                'result' => ['data' => 'test value'],
            ]);
        });

        test('toArray returns correct structure with numeric ID', function (): void {
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 42,
                result: 'simple string result',
            );

            $array = $data->toArray();

            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 42,
                'result' => 'simple string result',
            ]);
        });

        test('toArray returns correct structure with null ID', function (): void {
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: null,
                result: true,
            );

            $array = $data->toArray();

            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => null,
                'result' => true,
            ]);
        });

        test('toArray handles array result type', function (): void {
            $complexArray = [
                'users' => [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                'total' => 2,
                'page' => 1,
            ];

            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'request-001',
                result: $complexArray,
            );

            $array = $data->toArray();

            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 'request-001',
                'result' => $complexArray,
            ]);
        });

        test('toArray handles object result type', function (): void {
            $object = (object) ['property' => 'value', 'nested' => ['key' => 'val']];

            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 999,
                result: $object,
            );

            $array = $data->toArray();

            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 999,
                'result' => $object,
            ]);
        });

        test('toArray handles scalar result types', function (): void {
            // Integer result
            $intData = new MethodResultData('2.0', 'id-1', 123);
            expect($intData->toArray())->toBe([
                'jsonrpc' => '2.0',
                'id' => 'id-1',
                'result' => 123,
            ]);

            // Float result
            $floatData = new MethodResultData('2.0', 'id-2', 3.14);
            expect($floatData->toArray())->toBe([
                'jsonrpc' => '2.0',
                'id' => 'id-2',
                'result' => 3.14,
            ]);

            // Boolean result
            $boolData = new MethodResultData('2.0', 'id-3', false);
            expect($boolData->toArray())->toBe([
                'jsonrpc' => '2.0',
                'id' => 'id-3',
                'result' => false,
            ]);

            // String result
            $stringData = new MethodResultData('2.0', 'id-4', 'success');
            expect($stringData->toArray())->toBe([
                'jsonrpc' => '2.0',
                'id' => 'id-4',
                'result' => 'success',
            ]);
        });

        test('toArray handles null result', function (): void {
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'null-result-test',
                result: null,
            );

            $array = $data->toArray();

            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 'null-result-test',
                'result' => null,
            ]);
        });

        test('toArray preserves JSON-RPC version correctly', function (): void {
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 1,
                result: 'test',
            );

            $array = $data->toArray();

            expect($array)->toHaveKey('jsonrpc')
                ->and($array['jsonrpc'])->toBe('2.0');
        });

        test('toArray maintains field order as per JSON-RPC spec', function (): void {
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'order-test',
                result: ['ordered' => true],
            );

            $array = $data->toArray();
            $keys = array_keys($array);

            expect($keys)->toBe(['jsonrpc', 'id', 'result']);
        });

        test('toArray handles deeply nested structures', function (): void {
            $nestedResult = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => ['value' => 'deep'],
                        ],
                    ],
                ],
            ];

            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'deep-nest',
                result: $nestedResult,
            );

            $array = $data->toArray();

            expect($array['result'])->toBe($nestedResult)
                ->and($array['result']['level1']['level2']['level3']['level4']['value'])->toBe('deep');
        });

        test('toArray handles empty arrays and objects', function (): void {
            // Empty array result
            $emptyArrayData = new MethodResultData('2.0', 1, []);
            expect($emptyArrayData->toArray())->toBe([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [],
            ]);

            // Empty object result
            $emptyObjectData = new MethodResultData('2.0', 2, (object) []);
            $array = $emptyObjectData->toArray();
            expect($array['jsonrpc'])->toBe('2.0')
                ->and($array['id'])->toBe(2)
                ->and($array['result'])->toBeObject()
                ->and((array) $array['result'])->toBe([]);
        });
    });

    describe('Sad Paths', function (): void {
        test('handles non-standard jsonrpc version string', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '1.0',
                id: 'test-id',
                result: 'test',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['jsonrpc'])->toBe('1.0')
                ->and($array)->toHaveKey('jsonrpc')
                ->and($array)->toHaveKey('id')
                ->and($array)->toHaveKey('result');
        });

        test('preserves empty string values in result', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'empty-test',
                result: '',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBe('')
                ->and($array['result'])->not->toBeNull();
        });

        test('handles zero as id value', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 0,
                result: 'success',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe(0)
                ->and($array['id'])->not->toBeNull()
                ->and($array['id'])->not->toBeFalse();
        });

        test('handles zero as result value', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'zero-test',
                result: 0,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBe(0)
                ->and($array['result'])->not->toBeNull()
                ->and($array['result'])->not->toBeFalse();
        });

        test('handles empty string as id', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: '',
                result: 'data',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe('')
                ->and($array['id'])->not->toBeNull();
        });
    });

    describe('Edge Cases', function (): void {
        test('handles very large numeric id', function (): void {
            // Arrange
            $largeId = \PHP_INT_MAX;
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: $largeId,
                result: 'test',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe($largeId)
                ->and($array['id'])->toBeInt();
        });

        test('handles negative numeric id', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: -999,
                result: 'negative id test',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe(-999)
                ->and($array['id'])->toBeLessThan(0);
        });

        test('handles float id value', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 3.141_59,
                result: 'pi',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe(3.141_59)
                ->and($array['id'])->toBeFloat();
        });

        test('handles unicode characters in string id', function (): void {
            // Arrange
            $unicodeId = '测试-🚀-тест-テスト';
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: $unicodeId,
                result: 'unicode test',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe($unicodeId)
                ->and($array['id'])->toContain('🚀');
        });

        test('handles unicode characters in result', function (): void {
            // Arrange
            $unicodeResult = [
                'message' => 'Hello 世界 🌍',
                'emoji' => '👋🏼',
                'symbols' => '€£¥',
            ];
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'unicode-result',
                result: $unicodeResult,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBe($unicodeResult)
                ->and($array['result']['message'])->toContain('世界')
                ->and($array['result']['emoji'])->toBe('👋🏼');
        });

        test('handles very long string id', function (): void {
            // Arrange
            $longId = str_repeat('a', 10_000);
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: $longId,
                result: 'test',
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['id'])->toBe($longId)
                ->and($array['id'])->toHaveLength(10_000);
        });

        test('handles result with mixed array types', function (): void {
            // Arrange
            $mixedResult = [
                0 => 'indexed',
                'key' => 'associative',
                1 => 'another indexed',
                'nested' => ['deep' => 'value'],
            ];
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'mixed-array',
                result: $mixedResult,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBe($mixedResult)
                ->and($array['result'])->toHaveKey(0)
                ->and($array['result'])->toHaveKey('key');
        });

        test('handles result with resource-like structures', function (): void {
            // Arrange - simulate a serialized resource structure
            $resourceLike = [
                'type' => 'user',
                'id' => '123',
                'attributes' => ['name' => 'John'],
                'relationships' => null,
            ];
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'resource-test',
                result: $resourceLike,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBe($resourceLike)
                ->and($array['result']['type'])->toBe('user')
                ->and($array['result']['relationships'])->toBeNull();
        });

        test('handles boolean false as result without confusion with null', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'boolean-false',
                result: false,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBeFalse()
                ->and($array['result'])->not->toBeNull()
                ->and($array)->toHaveKey('result');
        });

        test('preserves exact field order in array representation', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'order-critical',
                result: ['a' => 1, 'b' => 2],
            );

            // Act
            $array = $data->toArray();
            $keys = array_keys($array);

            // Assert - JSON-RPC spec requires specific order
            expect($keys[0])->toBe('jsonrpc')
                ->and($keys[1])->toBe('id')
                ->and($keys[2])->toBe('result');
        });

        test('handles extremely nested array structures', function (): void {
            // Arrange - 10 levels deep
            $nested = ['level10' => 'deep'];

            for ($i = 9; $i >= 1; --$i) {
                $nested = ['level'.$i => $nested];
            }

            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'extreme-nesting',
                result: $nested,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result']['level1']['level2']['level3']['level4']['level5']['level6']['level7']['level8']['level9']['level10'])->toBe('deep');
        });

        test('handles result with special json characters', function (): void {
            // Arrange
            $specialChars = [
                'quotes' => 'He said "Hello"',
                'backslash' => 'C:\\Users\\Test',
                'newlines' => "Line1\nLine2\nLine3",
                'tabs' => "Col1\tCol2\tCol3",
            ];
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'special-chars',
                result: $specialChars,
            );

            // Act
            $array = $data->toArray();

            // Assert
            expect($array['result'])->toBe($specialChars)
                ->and($array['result']['quotes'])->toContain('"')
                ->and($array['result']['newlines'])->toContain("\n");
        });

        test('serializes to valid json structure', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 'json-test',
                result: ['status' => 'ok', 'count' => 42],
            );

            // Act
            $json = json_encode($data);
            $decoded = json_decode($json, true);

            // Assert
            expect($json)->toBeJson()
                ->and($decoded)->toHaveKey('jsonrpc')
                ->and($decoded)->toHaveKey('id')
                ->and($decoded)->toHaveKey('result')
                ->and($decoded['jsonrpc'])->toBe('2.0')
                ->and($decoded['id'])->toBe('json-test')
                ->and($decoded['result']['status'])->toBe('ok');
        });

        test('toArray is json serializable compliant', function (): void {
            // Arrange
            $data = new MethodResultData(
                jsonrpc: '2.0',
                id: 123,
                result: ['data' => ['nested' => true]],
            );

            // Act
            $array = $data->toArray();
            $jsonEncoded = json_encode($array);

            // Assert
            expect($jsonEncoded)->toBeJson()
                ->and(json_last_error())->toBe(\JSON_ERROR_NONE);
        });
    });
});
