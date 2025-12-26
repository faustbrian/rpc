<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\ErrorData;
use Cline\RPC\Data\ResponseData;
use Cline\RPC\Exceptions\InternalErrorException;

/**
 * @covers \Cline\RPC\Data\ResponseData
 *
 * @group data
 * @group response
 */
describe('ResponseData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates notification response via asNotification factory', function (): void {
            // Arrange
            // No setup needed for factory method

            // Act
            $response = ResponseData::asNotification();

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->jsonrpc)->toBe('2.0');
            expect($response->id)->toBeNull();
            expect($response->result)->toBeNull();
            expect($response->error)->toBeNull();
            expect($response->isNotification())->toBeTrue();
        });

        test('creates error response from request exception', function (): void {
            // Arrange
            $exception = InternalErrorException::create(
                new Exception('Test error'),
            );

            // Act
            $response = ResponseData::createFromRequestException($exception);

            // Assert
            expect($response)->toBeInstanceOf(ResponseData::class);
            expect($response->jsonrpc)->toBe('2.0');
            expect($response->error)->toBeInstanceOf(ErrorData::class);
            expect($response->error->code)->toBe(-32_603);
            expect($response->error->message)->toBe('Internal error');
            expect($response->isFailed())->toBeTrue();
        });

        test('identifies successful response with result data', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['data' => 'success'],
            ]);

            // Act
            $isSuccessful = $response->isSuccessful();

            // Assert
            expect($isSuccessful)->toBeTrue();
            expect($response->isFailed())->toBeFalse();
            expect($response->isNotification())->toBeFalse();
        });

        test('detects server error in isFailed method', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_603,
                'message' => 'Internal error',
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
                'error' => $errorData,
            ]);

            // Act
            $isFailed = $response->isFailed();
            $isServerError = $response->isServerError();

            // Assert
            expect($isFailed)->toBeTrue();
            expect($isServerError)->toBeTrue();
            expect($response->isSuccessful())->toBeFalse();
        });

        test('identifies notification response correctly', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
            ]);

            // Act
            $isNotification = $response->isNotification();

            // Assert
            expect($isNotification)->toBeTrue();
            expect($response->id)->toBeNull();
            expect($response->result)->toBeNull();
            expect($response->error)->toBeNull();
        });

        test('converts successful response to array correctly', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 123,
                'result' => ['status' => 'ok'],
            ]);

            // Act
            $array = $response->toArray();

            // Assert
            expect($array)->toBe([
                'jsonrpc' => '2.0',
                'id' => 123,
                'result' => ['status' => 'ok'],
            ]);
            expect($array)->not->toHaveKey('error');
        });

        test('converts error response to array correctly', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_600,
                'message' => 'Invalid Request',
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => $errorData,
            ]);

            // Act
            $array = $response->toArray();

            // Assert
            expect($array)->toHaveKey('jsonrpc');
            expect($array)->toHaveKey('id');
            expect($array)->toHaveKey('error');
            expect($array)->not->toHaveKey('result');
        });
    });

    describe('Sad Paths', function (): void {
        test('detects client error as failed response', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_600,
                'message' => 'Invalid Request',
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
                'error' => $errorData,
            ]);

            // Act
            $isFailed = $response->isFailed();
            $isClientError = $response->isClientError();

            // Assert
            expect($isFailed)->toBeTrue();
            expect($isClientError)->toBeTrue();
            expect($response->isServerError())->toBeFalse();
            expect($response->isSuccessful())->toBeFalse();
        });

        test('response with id is not a notification', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
            ]);

            // Act
            $isNotification = $response->isNotification();

            // Assert
            expect($isNotification)->toBeFalse();
            expect($response->id)->toBe(1);
        });

        test('response with result is not a notification', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'result' => 'some result',
            ]);

            // Act
            $isNotification = $response->isNotification();

            // Assert
            expect($isNotification)->toBeFalse();
            expect($response->result)->toBe('some result');
        });

        test('response with error is not a notification', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_700,
                'message' => 'Parse error',
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'error' => $errorData,
            ]);

            // Act
            $isNotification = $response->isNotification();

            // Assert
            expect($isNotification)->toBeFalse();
            expect($response->error)->toBeInstanceOf(ErrorData::class);
        });

        test('response without error is not failed', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => 'success',
            ]);

            // Act
            $isClientError = $response->isClientError();
            $isServerError = $response->isServerError();

            // Assert
            expect($isClientError)->toBeFalse();
            expect($isServerError)->toBeFalse();
            expect($response->isFailed())->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        test('handles server error code boundaries', function (int $code, string $message): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => $code,
                'message' => $message,
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'error' => $errorData,
            ]);

            // Act
            $isServerError = $response->isServerError();
            $isFailed = $response->isFailed();

            // Assert
            expect($isServerError)->toBeTrue();
            expect($isFailed)->toBeTrue();
        })->with([
            'lower boundary' => [-32_099, 'Server error at lower boundary'],
            'upper boundary' => [-32_000, 'Server error at upper boundary'],
            'middle range' => [-32_050, 'Server error in middle range'],
        ]);

        test('handles all client error codes', function (int $code, string $message): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => $code,
                'message' => $message,
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'error' => $errorData,
            ]);

            // Act
            $isClientError = $response->isClientError();
            $isFailed = $response->isFailed();
            $isServerError = $response->isServerError();

            // Assert
            expect($isClientError)->toBeTrue();
            expect($isFailed)->toBeTrue();
            expect($isServerError)->toBeFalse();
        })->with([
            'invalid request' => [-32_600, 'Invalid Request'],
            'method not found' => [-32_601, 'Method not found'],
            'invalid params' => [-32_602, 'Invalid params'],
        ]);

        test('handles parse error as server error', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_700,
                'message' => 'Parse error',
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'error' => $errorData,
            ]);

            // Act
            $isServerError = $response->isServerError();
            $isFailed = $response->isFailed();

            // Assert
            expect($isServerError)->toBeTrue();
            expect($isFailed)->toBeTrue();
            expect($response->isClientError())->toBeFalse();
        });

        test('handles response with all null optional fields as notification', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => null,
                'result' => null,
            ]);

            // Act
            $isNotification = $response->isNotification();

            // Assert
            expect($isNotification)->toBeTrue();
            expect($response->id)->toBeNull();
            expect($response->result)->toBeNull();
            expect($response->error)->toBeNull();
        });

        test('handles mixed id types correctly', function (mixed $id, bool $shouldBeNotification): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $id === null ? null : 'success',
            ]);

            // Act
            $isNotification = $response->isNotification();

            // Assert
            expect($isNotification)->toBe($shouldBeNotification);
        })->with([
            'string id' => ['string-id', false],
            'numeric id' => [42, false],
            'zero id' => [0, false],
            'null id' => [null, true],
            'float id' => [3.14, false],
            'negative id' => [-1, false],
        ]);
    });

    describe('Regression Tests', function (): void {
        test('ensures toArray excludes error field for successful responses', function (): void {
            // Arrange
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['status' => 'success'],
            ]);

            // Act
            $array = $response->toArray();

            // Assert
            expect($array)->not->toHaveKey('error');
            expect($array)->toHaveKey('result');
        });

        test('ensures toArray excludes result field for error responses', function (): void {
            // Arrange
            $errorData = ErrorData::from([
                'code' => -32_603,
                'message' => 'Internal error',
            ]);
            $response = ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => 1,
                'error' => $errorData,
            ]);

            // Act
            $array = $response->toArray();

            // Assert
            expect($array)->not->toHaveKey('result');
            expect($array)->toHaveKey('error');
        });

        test('validates notification factory produces exact structure', function (): void {
            // Arrange & Act
            $response = ResponseData::asNotification();
            $array = $response->toArray();

            // Assert
            expect($array)->toHaveKey('jsonrpc');
            expect($array['jsonrpc'])->toBe('2.0');
            expect($array)->toHaveKey('id');
            expect($array['id'])->toBeNull();
            expect($array)->toHaveKey('result');
            expect($array['result'])->toBeNull();
            expect($array)->toHaveCount(3);
        });
    });
});
