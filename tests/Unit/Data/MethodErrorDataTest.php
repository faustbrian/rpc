<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\ErrorData;
use Cline\RPC\Data\MethodErrorData;

describe('MethodErrorData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates instance from array', function (): void {
            $data = MethodErrorData::from([
                'jsonrpc' => '2.0',
                'id' => '123',
                'error' => ErrorData::from([
                    'code' => -32_600,
                    'message' => 'Invalid Request',
                    'data' => null,
                ]),
            ]);

            expect($data)->toBeInstanceOf(MethodErrorData::class)
                ->and($data->jsonrpc)->toBe('2.0')
                ->and($data->id)->toBe('123')
                ->and($data->error)->toBeInstanceOf(ErrorData::class);
        });

        test('toArray returns correct structure with string id', function (): void {
            $errorData = new ErrorData(
                code: -32_600,
                message: 'Invalid Request',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'test-123',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['jsonrpc', 'id', 'error'])
                ->and($result['jsonrpc'])->toBe('2.0')
                ->and($result['id'])->toBe('test-123')
                ->and($result['error'])->toBe($errorData);
        });

        test('toArray returns correct structure with numeric id', function (): void {
            $errorData = new ErrorData(
                code: -32_601,
                message: 'Method not found',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 42,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['jsonrpc', 'id', 'error'])
                ->and($result['jsonrpc'])->toBe('2.0')
                ->and($result['id'])->toBe(42)
                ->and($result['error'])->toBe($errorData);
        });

        test('toArray returns correct structure with null id', function (): void {
            $errorData = new ErrorData(
                code: -32_700,
                message: 'Parse error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: null,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['jsonrpc', 'id', 'error'])
                ->and($result['jsonrpc'])->toBe('2.0')
                ->and($result['id'])->toBeNull()
                ->and($result['error'])->toBe($errorData);
        });

        test('toArray preserves JSON-RPC version correctly', function (): void {
            $errorData = new ErrorData(
                code: -32_602,
                message: 'Invalid params',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'req-001',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['jsonrpc'])->toBe('2.0');
        });

        test('toArray works with ErrorData containing additional data', function (): void {
            $errorData = new ErrorData(
                code: -32_603,
                message: 'Internal error',
                data: ['details' => 'Database connection failed', 'timestamp' => 1_234_567_890],
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'complex-id',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['jsonrpc', 'id', 'error'])
                ->and($result['jsonrpc'])->toBe('2.0')
                ->and($result['id'])->toBe('complex-id')
                ->and($result['error'])->toBe($errorData)
                ->and($result['error']->data)->toBeArray()
                ->and($result['error']->data)->toHaveKey('details', 'Database connection failed')
                ->and($result['error']->data)->toHaveKey('timestamp', 1_234_567_890);
        });
    });

    describe('Edge Cases', function (): void {
        test('toArray handles float id correctly', function (): void {
            $errorData = new ErrorData(
                code: -32_000,
                message: 'Server error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 3.141_59,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe(3.141_59);
        });

        test('toArray handles boolean id correctly', function (): void {
            $errorData = new ErrorData(
                code: -32_001,
                message: 'Server implementation error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: true,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe(true);
        });

        test('toArray handles array id correctly', function (): void {
            $errorData = new ErrorData(
                code: -32_002,
                message: 'Server error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: ['batch', 'request', 1],
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe(['batch', 'request', 1]);
        });

        test('toArray maintains reference to same ErrorData instance', function (): void {
            $errorData = new ErrorData(
                code: -32_603,
                message: 'Internal error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'reference-test',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['error'])->toBe($errorData)
                ->and(spl_object_id($result['error']))->toBe(spl_object_id($errorData));
        });

        test('toArray handles empty string id', function (): void {
            $errorData = new ErrorData(
                code: -32_600,
                message: 'Invalid Request',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: '',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe('')
                ->and($result)->toHaveKey('id');
        });

        test('toArray handles zero numeric id', function (): void {
            $errorData = new ErrorData(
                code: -32_601,
                message: 'Method not found',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 0,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe(0)
                ->and($result)->toHaveKey('id');
        });

        test('toArray handles negative numeric id', function (): void {
            $errorData = new ErrorData(
                code: -32_602,
                message: 'Invalid params',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: -999,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe(-999);
        });

        test('toArray handles very long string id', function (): void {
            $longId = str_repeat('a', 10_000);
            $errorData = new ErrorData(
                code: -32_603,
                message: 'Internal error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: $longId,
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe($longId)
                ->and(mb_strlen((string) $result['id']))->toBe(10_000);
        });

        test('toArray handles unicode characters in id', function (): void {
            $errorData = new ErrorData(
                code: -32_000,
                message: 'Server error',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: '测试-🎯-тест',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['id'])->toBe('测试-🎯-тест');
        });

        test('toArray handles nested error data structures', function (): void {
            $complexData = [
                'trace' => [
                    ['file' => 'test.php', 'line' => 42],
                    ['file' => 'main.php', 'line' => 10],
                ],
                'context' => [
                    'user_id' => 123,
                    'session' => 'abc-def-ghi',
                ],
            ];

            $errorData = new ErrorData(
                code: -32_603,
                message: 'Internal error',
                data: $complexData,
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'nested-test',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['error']->data)->toBe($complexData)
                ->and($result['error']->data['trace'])->toHaveCount(2)
                ->and($result['error']->data['context']['user_id'])->toBe(123);
        });

        test('toArray returns array with exactly three keys', function (): void {
            $errorData = new ErrorData(
                code: -32_600,
                message: 'Invalid Request',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'key-count-test',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result)->toBeArray()
                ->and(array_keys($result))->toHaveCount(3)
                ->and(array_keys($result))->toBe(['jsonrpc', 'id', 'error']);
        });

        test('toArray maintains order of keys', function (): void {
            $errorData = new ErrorData(
                code: -32_601,
                message: 'Method not found',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'order-test',
                error: $errorData,
            );

            $result = $methodError->toArray();
            $keys = array_keys($result);

            expect($keys[0])->toBe('jsonrpc')
                ->and($keys[1])->toBe('id')
                ->and($keys[2])->toBe('error');
        });
    });

    describe('Sad Paths', function (): void {
        test('creates instance with non-standard jsonrpc version', function (): void {
            $errorData = new ErrorData(
                code: -32_600,
                message: 'Invalid Request',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '1.0',
                id: 'legacy',
                error: $errorData,
            );

            expect($methodError->jsonrpc)->toBe('1.0')
                ->and($methodError->toArray()['jsonrpc'])->toBe('1.0');
        });

        test('creates instance with empty jsonrpc version', function (): void {
            $errorData = new ErrorData(
                code: -32_600,
                message: 'Invalid Request',
            );

            $methodError = new MethodErrorData(
                jsonrpc: '',
                id: 'empty-version',
                error: $errorData,
            );

            expect($methodError->jsonrpc)->toBe('')
                ->and($methodError->toArray()['jsonrpc'])->toBe('');
        });

        test('toArray preserves all error codes including custom codes', function (): void {
            $errorData = new ErrorData(
                code: -99_999,
                message: 'Custom application error',
                data: ['custom_field' => 'custom_value'],
            );

            $methodError = new MethodErrorData(
                jsonrpc: '2.0',
                id: 'custom-error',
                error: $errorData,
            );

            $result = $methodError->toArray();

            expect($result['error']->code)->toBe(-99_999)
                ->and($result['error']->message)->toBe('Custom application error')
                ->and($result['error']->data['custom_field'])->toBe('custom_value');
        });
    });
});
