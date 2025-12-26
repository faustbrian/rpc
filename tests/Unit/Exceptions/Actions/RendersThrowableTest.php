<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\ErrorData;
use Cline\RPC\Exceptions\Actions\RendersThrowable;
use Cline\RPC\Exceptions\ForbiddenException;
use Cline\RPC\Exceptions\InternalErrorException;
use Cline\RPC\Exceptions\ResourceNotFoundException;
use Cline\RPC\Exceptions\SemanticValidationException;
use Cline\RPC\Exceptions\TooManyRequestsException;
use Cline\RPC\Exceptions\UnauthorizedException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Tests\Support\Fakes\CustomHeaderException;
use Tests\Support\Fakes\CustomStatusCodeException;

/**
 * @covers \Cline\RPC\Exceptions\Actions\RendersThrowable
 */
describe('RendersThrowable', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    describe('Happy Paths', function (): void {
        test('registers renderable on Exceptions instance', function (): void {
            // Arrange
            $exceptions = Mockery::mock(Exceptions::class);
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::type('callable'));

            // Act
            RendersThrowable::execute($exceptions);

            // Assert
            // Expectations are verified automatically by Mockery
        });

        test('returns JSON response for JSON request', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn('test-id');

            $throwable = new Exception('Test exception');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            $data = json_decode((string) $result->getContent(), true);
            expect($data)->toHaveKeys(['jsonrpc', 'id', 'error']);
            expect($data['jsonrpc'])->toBe('2.0');
            expect($data['id'])->toBe('test-id');
        });

        test('maps exception through ExceptionMapper', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(123);

            $originalException = new RuntimeException('Original error');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($originalException, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            $data = json_decode((string) $result->getContent(), true);
            expect($data['error'])->toHaveKeys(['code', 'message']);
            expect($data['error']['code'])->toBe(-32_603); // Internal error code
        });

        test('includes request ID in response', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn('unique-request-id');

            $throwable = new Exception('Test error');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            $data = json_decode((string) $result->getContent(), true);
            expect($data['id'])->toBe('unique-request-id');
        });

        test('maps authentication exception to unauthorized error', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $exception = new AuthenticationException();

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(401);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(UnauthorizedException::create()->getErrorCode());
        });

        test('maps authorization exception to forbidden error', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $exception = new AuthorizationException();

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(403);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(ForbiddenException::create()->getErrorCode());
        });

        test('maps model not found exception to resource not found error', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $exception = new ModelNotFoundException();

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(404);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(ResourceNotFoundException::create()->getErrorCode());
        });

        test('maps item not found exception to resource not found error', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $exception = new ItemNotFoundException();

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(404);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(ResourceNotFoundException::create()->getErrorCode());
        });

        test('maps throttle requests exception to too many requests error', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $exception = new ThrottleRequestsException();

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(429);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(TooManyRequestsException::create()->getErrorCode());
        });

        test('maps validation exception to unprocessable entity error with validation details', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $validator = Validator::make(['email' => 'invalid'], ['email' => ['required', 'email']]);
            $validator->fails();

            $exception = new ValidationException($validator);

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(422);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(SemanticValidationException::create()->getErrorCode());
            expect($data['error'])->toHaveKey('data');
        });

        test('maps generic exception to internal error', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $exception = new RuntimeException('Something went wrong');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($exception, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(500);

            $data = json_decode((string) $result->getContent(), true);
            expect($data['error']['code'])->toBe(InternalErrorException::create($exception)->getErrorCode());
        });
    });

    describe('Sad Paths', function (): void {
        test('returns null for non-JSON request', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(false);

            $throwable = new Exception('Test exception');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            expect($result)->toBeNull();
        });

        test('handles missing request ID gracefully', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(null);

            $throwable = new Exception('Test error');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            $data = json_decode((string) $result->getContent(), true);
            expect($data)->not->toHaveKey('id');
            expect($data)->toHaveKeys(['jsonrpc', 'error']);
        });
    });

    describe('Edge Cases', function (): void {
        test('filters null values from response', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(null);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            $content = $result->getContent();
            expect($content)->not->toContain('null');
            $data = json_decode($content, true);
            expect($data)->not->toHaveKey('id');
        });

        test('preserves exception headers', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $customException = new CustomHeaderException(
                ErrorData::from(['code' => -32_000, 'message' => 'Custom']),
            );

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($customException, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->headers->get('X-Custom-Header'))->toBe('CustomValue');
        });

        test('preserves exception status code', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $customException = new CustomStatusCodeException(
                ErrorData::from(['code' => -32_601, 'message' => 'Method not found']),
            );

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($customException, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            expect($result->getStatusCode())->toBe(404);
        });

        test('handles request with zero as id value', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(0);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert - zero is falsy and gets filtered out by array_filter
            $data = json_decode((string) $result->getContent(), true);
            expect($data)->not->toHaveKey('id'); // array_filter removes falsy values including 0
        });

        test('handles request with negative id value', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(-42);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            $data = json_decode((string) $result->getContent(), true);
            expect($data['id'])->toBe(-42);
        });

        test('handles request with string id', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn('uuid-string-123');

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert - JSON-RPC 2.0 allows string IDs
            $data = json_decode((string) $result->getContent(), true);
            expect($data['id'])->toBe('uuid-string-123');
        });

        test('handles exception with empty message', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $throwable = new RuntimeException('');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            $data = json_decode((string) $result->getContent(), true);
            expect($data['error'])->toHaveKey('message');
        });

        test('handles unicode characters in exception messages', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $throwable = new RuntimeException('Error: 你好 🚀 café');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
            $content = $result->getContent();
            expect(json_decode($content, true))->not->toBeNull(); // Valid JSON
        });

        test('handles very large request id', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(\PHP_INT_MAX);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            $data = json_decode((string) $result->getContent(), true);
            expect($data['id'])->toBe(\PHP_INT_MAX);
        });

        test('handles accept header with multiple types including json', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true); // Laravel handles this logic
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert
            expect($result)->toBeInstanceOf(JsonResponse::class);
        });
    });

    describe('Regressions', function (): void {
        test('ensures json rpc version is always 2.0', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert - prevents regression where version might be omitted or wrong
            $data = json_decode((string) $result->getContent(), true);
            expect($data['jsonrpc'])->toBe('2.0');
        });

        test('ensures error object is always present in response', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(1);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert - prevents regression where error might be missing
            $data = json_decode((string) $result->getContent(), true);
            expect($data)->toHaveKey('error');
            expect($data['error'])->toBeArray();
            expect($data['error'])->toHaveKey('code');
            expect($data['error'])->toHaveKey('message');
        });

        test('ensures array filter removes null id from response', function (): void {
            // Arrange
            $request = Mockery::mock(Request::class);
            $request->shouldReceive('wantsJson')->once()->andReturn(true);
            $request->shouldReceive('input')->with('id')->once()->andReturn(null);

            $throwable = new Exception('Test');

            $exceptions = Mockery::mock(Exceptions::class);
            $rendererCallback = null;
            $exceptions->shouldReceive('renderable')
                ->once()
                ->with(Mockery::on(function ($callback) use (&$rendererCallback): bool {
                    $rendererCallback = $callback;

                    return is_callable($callback);
                }));

            // Act
            RendersThrowable::execute($exceptions);
            $result = $rendererCallback($throwable, $request);

            // Assert - prevents regression where null id would appear in response
            $data = json_decode((string) $result->getContent(), true);
            expect($data)->not->toHaveKey('id');
        });
    });
});
