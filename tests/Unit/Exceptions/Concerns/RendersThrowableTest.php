<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\InternalErrorException;
use Cline\RPC\Exceptions\UnauthorizedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\Support\Fakes\ExceptionHandlerWithTrait;

describe('RendersThrowable', function (): void {
    beforeEach(function (): void {
        // Create a test class that uses the trait
        $this->handler = new ExceptionHandlerWithTrait();
    });

    test('renderableThrowable registers a renderable closure', function (): void {
        // Arrange
        $closures = [];
        $this->handler->setRenderableCallback(function ($closure) use (&$closures): void {
            $closures[] = $closure;
        });

        // Act
        $this->handler->callRenderableThrowable();

        // Assert
        expect($closures)->toHaveCount(1);
        expect($closures[0])->toBeCallable();
    });

    test('returns null when request does not want JSON', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_GET);
        $request->headers->set('Accept', 'text/html');

        $exception = new Exception('Test error');

        $this->handler->setRenderableCallback(function ($closure) use ($exception, $request): void {
            // Act
            $result = $closure($exception, $request);

            // Assert
            expect($result)->toBeNull();
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('maps exception through ExceptionMapper for JSON requests', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_GET);
        $request->headers->set('Accept', 'application/json');

        $originalException = new Exception('Test error');

        $this->handler->setRenderableCallback(function ($closure) use ($originalException, $request): void {
            // Act
            $response = $closure($originalException, $request);

            // Assert
            expect($response)->not->toBeNull();
            expect($response->getStatusCode())->toBe(500);

            $data = json_decode((string) $response->getContent(), true);
            expect($data)->toHaveKey('jsonrpc', '2.0');
            expect($data)->toHaveKey('error');
            expect($data['error'])->toHaveKey('code', -32_603);
            expect($data['error'])->toHaveKey('message', 'Internal error');
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('includes request ID in response when present', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST);
        $request->headers->set('Accept', 'application/json');
        $request->merge(['id' => 'test-id-123']);

        $exception = new Exception('Test error');

        $this->handler->setRenderableCallback(function ($closure) use ($exception, $request): void {
            // Act
            $response = $closure($exception, $request);

            // Assert
            $data = json_decode((string) $response->getContent(), true);
            expect($data)->toHaveKey('id', 'test-id-123');
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('filters out null ID when not present in request', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST);
        $request->headers->set('Accept', 'application/json');

        $exception = new Exception('Test error');

        $this->handler->setRenderableCallback(function ($closure) use ($exception, $request): void {
            // Act
            $response = $closure($exception, $request);

            // Assert
            $data = json_decode((string) $response->getContent(), true);
            expect($data)->not->toHaveKey('id');
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('uses status code from mapped exception', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_GET);
        $request->headers->set('Accept', 'application/json');

        $authException = new AuthenticationException('Unauthenticated');

        $this->handler->setRenderableCallback(function ($closure) use ($authException, $request): void {
            // Act
            $response = $closure($authException, $request);

            // Assert
            expect($response->getStatusCode())->toBe(401);
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('includes headers from mapped exception', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_GET);
        $request->headers->set('Accept', 'application/json');

        // Create an exception that has headers (UnauthorizedException)
        $unauthorizedException = UnauthorizedException::create();

        $this->handler->setRenderableCallback(function ($closure) use ($unauthorizedException, $request): void {
            // Act
            $response = $closure($unauthorizedException, $request);

            // Assert
            $headers = $response->headers->all();
            expect($headers)->toBeArray();
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('maps ValidationException to UnprocessableEntityException', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST);
        $request->headers->set('Accept', 'application/json');

        $validator = Validator::make(['email' => 'invalid'], ['email' => ['required', 'email']]);
        $validationException = new ValidationException($validator);

        $this->handler->setRenderableCallback(function ($closure) use ($validationException, $request): void {
            // Act
            $response = $closure($validationException, $request);

            // Assert
            expect($response->getStatusCode())->toBe(422);

            $data = json_decode((string) $response->getContent(), true);
            expect($data['error'])->toHaveKey('code', -32_000);
            expect($data['error'])->toHaveKey('message');
            expect($data['error'])->toHaveKey('data');
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('preserves JSON-RPC compliant exception without remapping', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_GET);
        $request->headers->set('Accept', 'application/json');

        $jsonRpcException = InternalErrorException::create(
            new Exception('Original error'),
        );

        $this->handler->setRenderableCallback(function ($closure) use ($jsonRpcException, $request): void {
            // Act
            $response = $closure($jsonRpcException, $request);

            // Assert
            expect($response->getStatusCode())->toBe(500);

            $data = json_decode((string) $response->getContent(), true);
            expect($data['error']['code'])->toBe(-32_603);
            expect($data['error']['message'])->toBe('Internal error');
            expect($data['error']['data'])->toBeArray();
        });

        // Act
        $this->handler->callRenderableThrowable();
    });

    test('returns JSON-RPC 2.0 compliant response structure', function (): void {
        // Arrange
        $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST);
        $request->headers->set('Accept', 'application/json');
        $request->merge(['id' => 42]);

        $exception = new Exception('Test error');

        $this->handler->setRenderableCallback(function ($closure) use ($exception, $request): void {
            // Act
            $response = $closure($exception, $request);

            // Assert
            $data = json_decode((string) $response->getContent(), true);

            // Verify JSON-RPC 2.0 structure
            expect($data)->toHaveKey('jsonrpc');
            expect($data['jsonrpc'])->toBe('2.0');
            expect($data)->toHaveKey('id');
            expect($data['id'])->toBe(42);
            expect($data)->toHaveKey('error');
            expect($data)->not->toHaveKey('result'); // Error responses should not have result

            // Verify error object structure
            expect($data['error'])->toHaveKeys(['code', 'message']);
            expect($data['error']['code'])->toBeInt();
            expect($data['error']['message'])->toBeString();
        });

        // Act
        $this->handler->callRenderableThrowable();
    });
});
