<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Route;
use Tests\Support\Fakes\Server;
use Tests\Support\MethodCaller;

// These tests are based on the examples from https://www.jsonrpc.org/specification

describe('MethodController', function (): void {
    beforeEach(function (): void {
        Route::rpc(Server::class);
    });

    describe('Happy Paths', function (): void {
        test('rpc.discover (OpenRPC)', function (): void {
            MethodCaller::call('rpc-discover');
        });
    });

    test('rpc call with positional parameters', function (): void {
        MethodCaller::call('rpc-call-with-positional-parameters-1');
        MethodCaller::call('rpc-call-with-positional-parameters-2');
    });

    test('rpc call with named parameters', function (): void {
        MethodCaller::call('rpc-call-with-named-parameters-1');
        MethodCaller::call('rpc-call-with-named-parameters-2');
    });

    test('rpc call with a Notification', function (): void {
        MethodCaller::call('rpc-call-with-a-notification');
    });

    test('rpc call of non-existent method', function (): void {
        MethodCaller::call('rpc-call-of-non-existent-method');
    });

    test('rpc call with invalid JSON', function (): void {
        MethodCaller::call('rpc-call-with-invalid-json', 400);
    });

    test('rpc call with invalid Request object', function (): void {
        MethodCaller::call('rpc-call-with-invalid-request-object');
    });

    test('rpc call Batch, invalid JSON', function (): void {
        MethodCaller::call('rpc-call-batch-invalid-json', 400);
    });

    test('rpc call with an empty Array', function (): void {
        MethodCaller::call('rpc-call-with-an-empty-array', 400);
    });

    test('rpc call with an invalid Batch (but not empty)', function (): void {
        MethodCaller::call('rpc-call-with-an-invalid-batch-but-not-empty');
    });

    test('rpc call with invalid Batch', function (): void {
        MethodCaller::call('rpc-call-with-invalid-batch');
    });

    test('rpc call Batch', function (): void {
        MethodCaller::call('rpc-call-batch');
    });

    test('rpc call Batch (all notifications)', function (): void {
        MethodCaller::call('rpc-call-batch-all-notifications');
    });

    describe('Collection and Data Responses', function (): void {
        test('rpc call with Collection response', function (): void {
            MethodCaller::call('rpc-call-collection-response');
        });

        test('rpc call with Spatie Data response', function (): void {
            MethodCaller::call('rpc-call-spatie-data-response');
        });
    });
});
