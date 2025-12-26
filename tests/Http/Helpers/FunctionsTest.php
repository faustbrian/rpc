<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Route;
use Tests\Support\Fakes\Server;

use function Cline\RPC\post_json_rpc;

describe('Helper Functions', function (): void {
    beforeEach(function (): void {
        Route::rpc(Server::class);
    });

    test('post_json_rpc sends JSON-RPC request', function (): void {
        if (!function_exists('Cline\RPC\post_json_rpc')) {
            $this->markTestSkipped('post_json_rpc function not available');
        }

        $response = post_json_rpc('test.subtract', ['minuend' => 42, 'subtrahend' => 23]);

        $response->assertOk();
        $response->assertJson(['jsonrpc' => '2.0']);
    });
});
