<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\ErrorData;

describe('ErrorData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates error data with code, message and data', function (): void {
            $error = new ErrorData(
                code: -32_600,
                message: 'Invalid Request',
                data: ['additional' => 'info'],
            );

            expect($error)->toBeInstanceOf(ErrorData::class)
                ->and($error->code)->toBe(-32_600)
                ->and($error->message)->toBe('Invalid Request')
                ->and($error->data)->toBe(['additional' => 'info']);
        });

        test('creates error data without additional data', function (): void {
            $error = new ErrorData(
                code: -32_601,
                message: 'Method not found',
            );

            expect($error)->toBeInstanceOf(ErrorData::class)
                ->and($error->code)->toBe(-32_601)
                ->and($error->message)->toBe('Method not found')
                ->and($error->data)->toBeNull();
        });

        test('creates from array using inherited from method', function (): void {
            $error = ErrorData::from([
                'code' => -32_602,
                'message' => 'Invalid params',
                'data' => 'Extra information',
            ]);

            expect($error)->toBeInstanceOf(ErrorData::class)
                ->and($error->code)->toBe(-32_602)
                ->and($error->message)->toBe('Invalid params')
                ->and($error->data)->toBe('Extra information');
        });
    });

    describe('Client Error Detection', function (): void {
        test('identifies invalid request as client error', function (): void {
            $error = new ErrorData(-32_600, 'Invalid Request');

            expect($error->isClient())->toBeTrue()
                ->and($error->isServer())->toBeFalse();
        });

        test('identifies method not found as client error', function (): void {
            $error = new ErrorData(-32_601, 'Method not found');

            expect($error->isClient())->toBeTrue()
                ->and($error->isServer())->toBeFalse();
        });

        test('identifies invalid params as client error', function (): void {
            $error = new ErrorData(-32_602, 'Invalid params');

            expect($error->isClient())->toBeTrue()
                ->and($error->isServer())->toBeFalse();
        });

        test('does not identify parse error as client error', function (): void {
            $error = new ErrorData(-32_700, 'Parse error');

            expect($error->isClient())->toBeFalse();
        });

        test('does not identify internal error as client error', function (): void {
            $error = new ErrorData(-32_603, 'Internal error');

            expect($error->isClient())->toBeFalse();
        });
    });

    describe('Server Error Detection', function (): void {
        test('identifies parse error as server error', function (): void {
            // Covers line 77 (condition) and line 78 (return true)
            $error = new ErrorData(-32_700, 'Parse error');

            expect($error->isServer())->toBeTrue()
                ->and($error->isClient())->toBeFalse();
        });

        test('identifies internal error as server error', function (): void {
            // Covers line 82 (condition) and line 83 (return true)
            $error = new ErrorData(-32_603, 'Internal error');

            expect($error->isServer())->toBeTrue()
                ->and($error->isClient())->toBeFalse();
        });

        test('identifies implementation-defined server errors', function (): void {
            // Test lower boundary
            $error1 = new ErrorData(-32_099, 'Server error at lower boundary');
            expect($error1->isServer())->toBeTrue()
                ->and($error1->isClient())->toBeFalse();

            // Test upper boundary
            $error2 = new ErrorData(-32_000, 'Server error at upper boundary');
            expect($error2->isServer())->toBeTrue()
                ->and($error2->isClient())->toBeFalse();

            // Test middle value
            $error3 = new ErrorData(-32_050, 'Server error in middle');
            expect($error3->isServer())->toBeTrue()
                ->and($error3->isClient())->toBeFalse();
        });

        test('does not identify client errors as server errors', function (): void {
            $error1 = new ErrorData(-32_600, 'Invalid Request');
            expect($error1->isServer())->toBeFalse();

            $error2 = new ErrorData(-32_601, 'Method not found');
            expect($error2->isServer())->toBeFalse();

            $error3 = new ErrorData(-32_602, 'Invalid params');
            expect($error3->isServer())->toBeFalse();
        });

        test('does not identify custom errors outside reserved range as server errors', function (): void {
            $error1 = new ErrorData(100, 'Custom error');
            expect($error1->isServer())->toBeFalse()
                ->and($error1->isClient())->toBeFalse();

            $error2 = new ErrorData(-31_999, 'Just outside server range');
            expect($error2->isServer())->toBeFalse()
                ->and($error2->isClient())->toBeFalse();

            $error3 = new ErrorData(-32_100, 'Just below server range');
            expect($error3->isServer())->toBeFalse()
                ->and($error3->isClient())->toBeFalse();
        });
    });

    describe('HTTP Status Code Mapping', function (): void {
        test('maps parse error to 500 status code', function (): void {
            // Covers line 104: $this->code === -32_700 => 500
            $error = new ErrorData(-32_700, 'Parse error');

            expect($error->toStatusCode())->toBe(500);
        });

        test('maps invalid request to 400 status code', function (): void {
            // Covers line 105: $this->code === -32_600 => 400
            $error = new ErrorData(-32_600, 'Invalid Request');

            expect($error->toStatusCode())->toBe(400);
        });

        test('maps method not found to 404 status code', function (): void {
            // Covers line 106: $this->code === -32_601 => 404
            $error = new ErrorData(-32_601, 'Method not found');

            expect($error->toStatusCode())->toBe(404);
        });

        test('maps invalid params to 500 status code', function (): void {
            // Covers line 107: $this->code === -32_602 => 500
            $error = new ErrorData(-32_602, 'Invalid params');

            expect($error->toStatusCode())->toBe(500);
        });

        test('maps internal error to 500 status code', function (): void {
            // Covers line 108: $this->code === -32_603 => 500
            $error = new ErrorData(-32_603, 'Internal error');

            expect($error->toStatusCode())->toBe(500);
        });

        test('maps implementation-defined server errors to 500 status code', function (): void {
            // Covers line 109: $this->isServer() => 500
            // This is the fallback for server errors not specifically matched above
            $error1 = new ErrorData(-32_050, 'Custom server error');
            expect($error1->toStatusCode())->toBe(500);

            $error2 = new ErrorData(-32_099, 'Lower boundary server error');
            expect($error2->toStatusCode())->toBe(500);

            $error3 = new ErrorData(-32_000, 'Upper boundary server error');
            expect($error3->toStatusCode())->toBe(500);
        });

        test('maps non-standard error codes to 200 status code', function (): void {
            // Covers line 111: default => 200
            // NOTE: Line 110 ($this->isClient() => 400) is unreachable code because
            // all client error codes (-32600, -32601, -32602) are already matched
            // in lines 105-107. The isClient() method only returns true for those
            // three codes, so this fallback can never be reached.

            $error1 = new ErrorData(1, 'Application specific error');
            expect($error1->toStatusCode())->toBe(200);

            $error2 = new ErrorData(-100, 'Custom negative error');
            expect($error2->toStatusCode())->toBe(200);

            $error3 = new ErrorData(404, 'HTTP-like but not JSON-RPC code');
            expect($error3->toStatusCode())->toBe(200);

            $error4 = new ErrorData(-31_999, 'Just outside reserved range');
            expect($error4->toStatusCode())->toBe(200);

            $error5 = new ErrorData(-32_800, 'Below parse error code');
            expect($error5->toStatusCode())->toBe(200);
        });
    });

    describe('Edge Cases', function (): void {
        test('handles zero error code', function (): void {
            $error = new ErrorData(0, 'Zero code');

            expect($error->isClient())->toBeFalse()
                ->and($error->isServer())->toBeFalse()
                ->and($error->toStatusCode())->toBe(200);
        });

        test('handles maximum positive integer code', function (): void {
            $error = new ErrorData(\PHP_INT_MAX, 'Max int');

            expect($error->isClient())->toBeFalse()
                ->and($error->isServer())->toBeFalse()
                ->and($error->toStatusCode())->toBe(200);
        });

        test('handles minimum negative integer code', function (): void {
            $error = new ErrorData(\PHP_INT_MIN, 'Min int');

            expect($error->isClient())->toBeFalse()
                ->and($error->isServer())->toBeFalse()
                ->and($error->toStatusCode())->toBe(200);
        });

        test('handles error codes just outside server range boundaries', function (): void {
            // Test code just below server range (-32100)
            $errorBelow = new ErrorData(-32_100, 'Just below server range');
            expect($errorBelow->isServer())->toBeFalse()
                ->and($errorBelow->toStatusCode())->toBe(200);

            // Test code just above server range (-31999)
            $errorAbove = new ErrorData(-31_999, 'Just above server range');
            expect($errorAbove->isServer())->toBeFalse()
                ->and($errorAbove->toStatusCode())->toBe(200);
        });

        test('handles error with null data field', function (): void {
            $error = new ErrorData(-32_600, 'Error with null data');

            expect($error->data)->toBeNull();

            // When converting to array, null values should be removed
            $array = $error->toArray();
            expect($array)->not->toHaveKey('data')
                ->and($array['code'])->toBe(-32_600)
                ->and($array['message'])->toBe('Error with null data');
        });

        test('handles error with empty string data', function (): void {
            $error = new ErrorData(-32_603, 'Error with empty string', '');

            expect($error->data)->toBe('');

            $array = $error->toArray();
            expect($array)->toHaveKey('data')
                ->and($array['data'])->toBe('');
        });

        test('handles error with scalar data types', function (): void {
            // String data
            $stringError = new ErrorData(-32_600, 'String data error', 'error details');
            expect($stringError->data)->toBe('error details');

            // Integer data
            $intError = new ErrorData(-32_601, 'Integer data error', 42);
            expect($intError->data)->toBe(42);

            // Boolean data
            $boolError = new ErrorData(-32_602, 'Boolean data error', false);
            expect($boolError->data)->toBe(false);

            // Float data
            $floatError = new ErrorData(-32_603, 'Float data error', 3.14);
            expect($floatError->data)->toBe(3.14);
        });

        test('handles error with complex data structures', function (): void {
            $complexData = [
                'nested' => [
                    'array' => ['with', 'values'],
                    'object' => ['key' => 'value'],
                ],
                'string' => 'data',
                'number' => 123,
                'boolean' => true,
            ];

            $error = new ErrorData(-32_603, 'Complex error', $complexData);

            expect($error->data)->toBe($complexData);
        });

        test('handles error with unicode characters in message', function (): void {
            $unicodeMessage = 'Error: 日本語 🚀 Ошибка';
            $error = new ErrorData(-32_600, $unicodeMessage);

            expect($error->message)->toBe($unicodeMessage);
        });

        test('handles error with very long message', function (): void {
            $longMessage = str_repeat('Error details ', 100);
            $error = new ErrorData(-32_603, $longMessage);

            expect($error->message)->toBe($longMessage)
                ->and(mb_strlen($error->message))->toBeGreaterThan(1_000);
        });
    });

    describe('Regression Tests', function (): void {
        test('ensures parse error is correctly classified as server error not client', function (): void {
            // Regression: Parse error might be mistakenly classified as client error
            $error = new ErrorData(-32_700, 'Parse error');

            expect($error->isServer())->toBeTrue()
                ->and($error->isClient())->toBeFalse()
                ->and($error->toStatusCode())->toBe(500);
        });

        test('ensures all predefined error codes map to correct status codes', function (): void {
            // Comprehensive test of all predefined codes
            $mappings = [
                [-32_700, 500], // Parse error
                [-32_600, 400], // Invalid Request
                [-32_601, 404], // Method not found
                [-32_602, 500], // Invalid params (note: maps to 500, not 400)
                [-32_603, 500], // Internal error
            ];

            foreach ($mappings as [$code, $expectedStatus]) {
                $error = new ErrorData($code, 'Test message');
                expect($error->toStatusCode())->toBe($expectedStatus);
            }
        });

        test('ensures implementation-defined server error range is inclusive', function (): void {
            // Regression: Boundary conditions for server error range
            $lowerBoundary = new ErrorData(-32_099, 'Lower boundary');
            $upperBoundary = new ErrorData(-32_000, 'Upper boundary');
            $justOutsideLower = new ErrorData(-32_100, 'Just outside lower');
            $justOutsideUpper = new ErrorData(-31_999, 'Just outside upper');

            expect($lowerBoundary->isServer())->toBeTrue();
            expect($upperBoundary->isServer())->toBeTrue();
            expect($justOutsideLower->isServer())->toBeFalse();
            expect($justOutsideUpper->isServer())->toBeFalse();
        });
    });
});
