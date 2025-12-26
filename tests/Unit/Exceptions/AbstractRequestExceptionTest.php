<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\ErrorData;
use Cline\RPC\Exceptions\AbstractRequestException;
use Illuminate\Support\Facades\Config;
use Tests\Unit\Exceptions\Fixtures\ConcreteRequestException;

describe('AbstractRequestException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates exception with error data', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_600,
                'message' => 'Invalid Request',
                'data' => ['detail' => 'Missing required field'],
            ]);

            // Act
            $exception = new ConcreteRequestException($errorData);

            // Assert
            expect($exception)->toBeInstanceOf(AbstractRequestException::class);
            expect($exception->getErrorCode())->toBe(-32_600);
            expect($exception->getErrorMessage())->toBe('Invalid Request');
            expect($exception->getErrorData())->toBe(['detail' => 'Missing required field']);
        });

        test('returns error data via getErrorData', function (): void {
            // Arrange
            $data = ['validation' => ['field' => 'email', 'error' => 'invalid format']];
            $exception = ConcreteRequestException::make(-32_602, 'Invalid params', $data);

            // Act
            $result = $exception->getErrorData();

            // Assert
            expect($result)->toBe($data);
        });

        test('converts exception to error data object', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_601, 'Method not found');

            // Act
            $errorData = $exception->toError();

            // Assert
            expect($errorData)->toBeInstanceOf(ErrorData::class);
            expect($errorData->code)->toBe(-32_601);
            expect($errorData->message)->toBe('Method not found');
        });

        test('converts exception to array representation', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(
                -32_602,
                'Invalid params',
                ['field' => 'email'],
            );

            // Act
            $array = $exception->toArray();

            // Assert
            expect($array)->toHaveKeys(['code', 'message', 'data']);
            expect($array['code'])->toBe(-32_602);
            expect($array['message'])->toBe('Invalid params');
            expect($array['data'])->toBe(['field' => 'email']);
        });

        test('returns empty headers by default', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_600, 'Invalid Request');

            // Act
            $headers = $exception->getHeaders();

            // Assert
            expect($headers)->toBe([]);
            expect($headers)->toBeArray();
            expect($headers)->toBeEmpty();
        });
    });

    describe('Sad Paths', function (): void {
        test('handles exception with null data', function (): void {
            // Arrange & Act
            $exception = ConcreteRequestException::make(-32_603, 'Internal error');

            // Assert
            expect($exception->getErrorData())->toBeNull();
            expect($exception->toArray())->toHaveKey('code');
            expect($exception->toArray())->toHaveKey('message');
        });
    });

    describe('Edge Cases', function (): void {
        test('includes debug information when debug mode is enabled', function (): void {
            // Arrange
            Config::set('app.debug', true);
            $exception = ConcreteRequestException::make(-32_603, 'Internal error');

            // Act
            $array = $exception->toArray();

            // Assert
            expect($array)->toHaveKey('data');
            expect($array['data'])->toHaveKey('debug');
            expect($array['data']['debug'])->toHaveKeys(['file', 'line', 'trace']);
            expect($array['data']['debug']['file'])->toBeString();
            expect($array['data']['debug']['line'])->toBeInt();
            expect($array['data']['debug']['trace'])->toBeString();
        });

        test('excludes debug information when debug mode is disabled', function (): void {
            // Arrange
            Config::set('app.debug', false);
            $exception = ConcreteRequestException::make(-32_603, 'Internal error');

            // Act
            $array = $exception->toArray();

            // Assert
            expect($array)->toHaveKeys(['code', 'message']);
            expect($array)->not->toHaveKey('data');
        });

        test('filters out null values in array representation', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_600, 'Invalid Request');
            Config::set('app.debug', false);

            // Act
            $array = $exception->toArray();

            // Assert
            expect($array)->not->toHaveKey('data');
            expect($array)->toHaveCount(2); // Only code and message
        });

        test('merges debug data with existing error data', function (): void {
            // Arrange
            Config::set('app.debug', true);
            $existingData = ['validation' => 'failed'];
            $exception = ConcreteRequestException::make(-32_602, 'Invalid params', $existingData);

            // Act
            $array = $exception->toArray();

            // Assert
            expect($array['data'])->toHaveKey('validation');
            expect($array['data'])->toHaveKey('debug');
            expect($array['data']['validation'])->toBe('failed');
            expect($array['data']['debug'])->toHaveKeys(['file', 'line', 'trace']);
        });

        test('handles empty data array', function (): void {
            // Arrange
            Config::set('app.debug', false);
            $exception = ConcreteRequestException::make(-32_600, 'Invalid Request', []);

            // Act
            $array = $exception->toArray();

            // Assert
            expect($array)->toHaveKeys(['code', 'message']);
            // Empty array should be filtered out by array_filter
            expect($array)->not->toHaveKey('data');
        });
    });

    describe('getStatusCode - Standard Error Codes', function (): void {
        test('returns 400 for parse error (-32700)', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_700, 'Parse error');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(400);
        });

        test('returns 400 for invalid request (-32600)', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_600, 'Invalid Request');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(400);
        });

        test('returns 404 for method not found (-32601)', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_601, 'Method not found');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(404);
        });

        test('returns 400 for invalid params (-32602)', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_602, 'Invalid params');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(400);
        });

        test('returns 500 for internal error (-32603)', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_603, 'Internal error');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(500);
        });

        test('returns 500 for non-standard error codes (default)', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_000, 'Server error');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(500);
        });

        test('returns 500 for custom error codes', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(-32_099, 'Custom server error');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(500);
        });

        test('returns 500 for positive error codes', function (): void {
            // Arrange
            $exception = ConcreteRequestException::make(1_000, 'Custom error');

            // Act
            $statusCode = $exception->getStatusCode();

            // Assert
            expect($statusCode)->toBe(500);
        });
    });
});
