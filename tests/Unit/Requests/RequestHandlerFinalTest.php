<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Contracts\ServerInterface;
use Cline\RPC\Requests\RequestHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Tests\Support\Fakes\Server;

describe('RequestHandler Complete Coverage', function (): void {
    beforeEach(function (): void {
        Route::rpc(Server::class);
        App::bind(ServerInterface::class, Server::class);
    });

    describe('Happy Paths', function (): void {
        test('can call a method from an array', function (): void {
            $result = RequestHandler::createFromArray([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'app.subtract_with_binding',
                'params' => [
                    'data' => ['subtrahend' => 23, 'minuend' => 42],
                ],
            ]);

            expect($result->toArray())->toBe([
                'data' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => 19,
                ],
                'statusCode' => 200,
                'headers' => [],
            ]);
        });

        test('can call a method from a string', function (): void {
            $result = RequestHandler::createFromString(
                json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'app.subtract_with_binding',
                    'params' => [
                        'data' => ['subtrahend' => 23, 'minuend' => 42],
                    ],
                ]),
            );

            expect($result->toArray())->toBe([
                'data' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => 19,
                ],
                'statusCode' => 200,
                'headers' => [],
            ]);
        });
    });

    describe('Method Exception Handling', function (): void {
        test('handles authentication exception from method', function (): void {
            // Arrange
            $request = [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'app.requires_authentication',
                'params' => [],
            ];

            // Act
            $result = RequestHandler::createFromArray($request);

            // Assert - Exception is caught in method execution loop and mapped
            expect($result->statusCode)->toBe(200);
            expect($result->data->error->code)->toBe(-32_000);
            expect($result->data->error->message)->toBe('Server error');
            // Authentication exceptions are properly mapped
            expect($result->data->error)->toHaveProperty('data');
        });

        test('handles authorization exception from method', function (): void {
            // Arrange
            $request = [
                'jsonrpc' => '2.0',
                'id' => 4,
                'method' => 'app.requires_authorization',
                'params' => [],
            ];

            // Act
            $result = RequestHandler::createFromArray($request);

            // Assert - Exception is caught in method execution loop and mapped
            expect($result->statusCode)->toBe(200);
            expect($result->data->error->code)->toBe(-32_000);
            expect($result->data->error->message)->toBe('Server error');
            // Authorization exceptions are properly mapped
            expect($result->data->error)->toHaveProperty('data');
        });
    });

    describe('Batch Requests', function (): void {
        test('handles mixed success and auth errors in batch', function (): void {
            // Arrange
            $batchRequest = [
                [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'app.sum',
                    'params' => ['data' => [1, 2, 3]],
                ],
                [
                    'jsonrpc' => '2.0',
                    'id' => 2,
                    'method' => 'app.requires_authentication',
                    'params' => [],
                ],
            ];

            // Act
            $result = RequestHandler::createFromArray($batchRequest);

            // Assert
            expect($result->statusCode)->toBe(200);
            expect($result->data)->toHaveCount(2);

            // First should succeed
            expect($result->data[0]->jsonrpc)->toBe('2.0');
            expect($result->data[0]->id)->toBe(1);
            expect($result->data[0]->result)->toBe(6);

            // Second should have auth error
            expect($result->data[1]->jsonrpc)->toBe('2.0');
            expect($result->data[1]->id)->toBe(2);
            expect($result->data[1]->error->code)->toBe(-32_000);
        });
    });

    describe('Notifications', function (): void {
        test('notifications with auth errors return no response', function (): void {
            // Arrange
            $notification = [
                'jsonrpc' => '2.0',
                'method' => 'app.requires_authentication',
                'params' => [],
            ];

            // Act
            $result = RequestHandler::createFromArray($notification);

            // Assert - Notifications never return responses, even on error
            expect($result->statusCode)->toBe(200);
            expect($result->data)->toBeEmpty();
        });

        test('batch with only notifications returns empty', function (): void {
            // Arrange
            $batchRequest = [
                [
                    'jsonrpc' => '2.0',
                    'method' => 'app.notify_hello',
                    'params' => ['world'],
                ],
                [
                    'jsonrpc' => '2.0',
                    'method' => 'app.requires_authentication',
                    'params' => [],
                ],
            ];

            // Act
            $result = RequestHandler::createFromArray($batchRequest);

            // Assert
            expect($result->statusCode)->toBe(200);
            expect($result->data)->toBeEmpty();
        });
    });
});
