<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\InternalErrorException;
use Cline\RPC\Exceptions\MethodNotFoundException;
use Cline\RPC\Exceptions\StructurallyInvalidRequestException;
use Cline\RPC\Http\Middleware\RenderThrowable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('RenderThrowable Middleware', function (): void {
    describe('Happy Paths', function (): void {
        test('passes request through when no exception is thrown', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

            $expectedResponse = new JsonResponse(['jsonrpc' => '2.0', 'result' => 'success', 'id' => '123']);
            $next = fn (Request $req): JsonResponse => $expectedResponse;

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBe($expectedResponse)
                ->and($response->getData(true))->toBe(['jsonrpc' => '2.0', 'result' => 'success', 'id' => '123']);
        });

        test('renders InvalidRequestException as JSON-RPC error', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => '456']);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(400);

            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc', '2.0')
                ->and($data)->toHaveKey('id', '456')
                ->and($data)->toHaveKey('error')
                ->and($data['error'])->toHaveKey('code', -32_600)
                ->and($data['error'])->toHaveKey('message');
        });

        test('renders MethodNotFoundException as JSON-RPC error', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'test-123']);

            $next = function (): void {
                throw MethodNotFoundException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(404);

            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc', '2.0')
                ->and($data)->toHaveKey('id', 'test-123')
                ->and($data)->toHaveKey('error')
                ->and($data['error'])->toHaveKey('code', -32_601)
                ->and($data['error'])->toHaveKey('message');
        });

        test('renders InternalErrorException as JSON-RPC error', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 789]);

            $next = function (): void {
                throw InternalErrorException::create(
                    new Exception('Test error'),
                );
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(500);

            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc', '2.0')
                ->and($data)->toHaveKey('id', 789)
                ->and($data)->toHaveKey('error')
                ->and($data['error'])->toHaveKey('code', -32_603)
                ->and($data['error'])->toHaveKey('message');
        });

        test('includes request id in error response when present', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'custom-uuid-123']);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            expect($data['id'])->toBe('custom-uuid-123');
        });

        test('handles numeric request id', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 42]);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            expect($data['id'])->toBe(42);
        });

        test('handles string request id', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'test-string-id']);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            expect($data['id'])->toBe('test-string-id');
        });
    });

    describe('Sad Paths', function (): void {
        test('re-throws exception when request does not want JSON', function (): void {
            // Arrange - Request without JSON Accept header
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST);
            $request->headers->set('Accept', 'text/html');

            $next = function (): void {
                throw new RuntimeException('Test exception');
            };

            // Act & Assert
            expect(fn (): Symfony\Component\HttpFoundation\Response => $middleware->handle($request, $next))
                ->toThrow(RuntimeException::class, 'Test exception');
        });
    });

    describe('Edge Cases', function (): void {
        test('handles exception with null request id', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            // No id in request (notification)

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc')
                ->and($data)->toHaveKey('error')
                ->and($data)->not->toHaveKey('id'); // Filtered out when null
        });

        test('handles generic PHP exception', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'test-id']);

            $next = function (): void {
                throw new Exception('Something went wrong');
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(500);

            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc', '2.0')
                ->and($data)->toHaveKey('error')
                ->and($data['error'])->toHaveKey('code', -32_603); // Internal error
        });

        test('handles TypeError exception', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'type-error-test']);

            $next = function (): void {
                throw new TypeError('Type error occurred');
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(500);

            $data = $response->getData(true);
            expect($data)->toHaveKey('error')
                ->and($data['error'])->toHaveKey('code', -32_603);
        });

        test('preserves response headers from mapped exception', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'header-test']);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->headers->get('Content-Type'))->toContain('application/json');
        });

        test('handles request with application/json Accept header', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'json-accept']);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class);

            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc', '2.0')
                ->and($data)->toHaveKey('error');
        });

        test('handles exception thrown during response building', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 'response-builder-test']);

            $next = function (): never {
                throw new RuntimeException('Unexpected error');
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class)
                ->and($response->getStatusCode())->toBe(500);
        });

        test('does not filter jsonrpc field from response', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc')
                ->and($data['jsonrpc'])->toBe('2.0'); // Always present
        });

        test('handles POST request with JSON body', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $requestBody = json_encode([
                'jsonrpc' => '2.0',
                'method' => 'test.method',
                'id' => 'body-test',
            ]);
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ], $requestBody);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($response)->toBeInstanceOf(JsonResponse::class);

            $data = $response->getData(true);
            expect($data)->toHaveKey('jsonrpc')
                ->and($data)->toHaveKey('error');
        });

        test('handles zero as request id', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => 0]);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            // Note: Zero is filtered by array_filter, which is expected JSON-RPC behavior
            expect($data)->not->toHaveKey('id');
        });

        test('handles empty string as request id', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $request->merge(['id' => '']);

            $next = function (): void {
                throw StructurallyInvalidRequestException::create();
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            $data = $response->getData(true);
            expect($data)->not->toHaveKey('id'); // Empty string filtered by array_filter
        });
    });

    describe('Regressions', function (): void {
        test('does not break request pipeline on success', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

            $nextCalled = false;
            $next = function (Request $req) use (&$nextCalled): Response {
                $nextCalled = true;

                return new Response('success');
            };

            // Act
            $response = $middleware->handle($request, $next);

            // Assert
            expect($nextCalled)->toBeTrue()
                ->and($response->getContent())->toBe('success');
        });

        test('maintains request object integrity', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $requestData = [
                'jsonrpc' => '2.0',
                'method' => 'test.method',
                'params' => ['key' => 'value'],
                'id' => 'integrity-test',
            ];
            $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, $requestData, [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer token123',
            ]);

            $capturedRequest = null;
            $next = function (Request $req) use (&$capturedRequest): Response {
                $capturedRequest = $req;

                return new Response('ok');
            };

            // Act
            $middleware->handle($request, $next);

            // Assert
            expect($capturedRequest)->not->toBeNull()
                ->and($capturedRequest->input('jsonrpc'))->toBe('2.0')
                ->and($capturedRequest->input('method'))->toBe('test.method')
                ->and($capturedRequest->input('id'))->toBe('integrity-test')
                ->and($capturedRequest->header('Authorization'))->toBe('Bearer token123');
        });

        test('consistent error format across different exception types', function (): void {
            // Arrange
            $middleware = new RenderThrowable();
            $exceptions = [
                StructurallyInvalidRequestException::create(),
                MethodNotFoundException::create(),
                InternalErrorException::create(
                    new Exception('Internal error test'),
                ),
            ];

            foreach ($exceptions as $exception) {
                $request = Request::create('/rpc', Symfony\Component\HttpFoundation\Request::METHOD_POST, [], [], [], [
                    'HTTP_ACCEPT' => 'application/json',
                ]);
                $request->merge(['id' => 'format-test']);

                $next = function () use ($exception): void {
                    throw $exception;
                };

                // Act
                $response = $middleware->handle($request, $next);

                // Assert
                $data = $response->getData(true);
                expect($data)->toHaveKey('jsonrpc', '2.0')
                    ->and($data)->toHaveKey('error')
                    ->and($data['error'])->toHaveKey('code')
                    ->and($data['error'])->toHaveKey('message')
                    ->and($data['error']['code'])->toBeInt();
            }
        });
    });
});
