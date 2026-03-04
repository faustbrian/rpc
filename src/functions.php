<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC;

use Illuminate\Testing\TestResponse;

use function array_filter;
use function function_exists;
use function Pest\Laravel\postJson;
use function route;

if (!function_exists('post_json_rpc') && function_exists('Pest\Laravel\postJson')) {
    /**
     * Helper function for testing JSON-RPC requests in Pest tests.
     *
     * Sends a JSON-RPC request to the default RPC endpoint with proper formatting.
     * Automatically includes the required jsonrpc version field and provides a
     * default request ID for testing. Only available when Pest is installed.
     *
     * ```php
     * // Simple RPC call without parameters
     * post_json_rpc('users.list')
     *     ->assertOk();
     *
     * // RPC call with parameters
     * post_json_rpc('users.find', ['id' => 1])
     *     ->assertJson(['result' => ['id' => 1]]);
     *
     * // RPC call with custom request ID
     * post_json_rpc('users.create', ['name' => 'John'], 'custom-id-123')
     *     ->assertJson(['id' => 'custom-id-123']);
     * ```
     *
     * @param  string               $method The RPC method name to invoke (e.g., "users.list")
     * @param  array<string, mixed> $params Optional parameters to pass to the RPC method
     * @param  string               $id     Optional custom request ID for request/response correlation.
     *                                      Defaults to a ULID identifier if not provided.
     * @return TestResponse         Test response for fluent assertions
     */
    function post_json_rpc(string $method, ?array $params = null, ?string $id = null): TestResponse
    {
        return postJson(
            route('rpc'),
            array_filter([
                'jsonrpc' => '2.0',
                'id' => $id ?? '01J34641TE5SF58ZX3N9HPT1BA',
                'method' => $method,
                'params' => $params,
            ]),
        );
    }
}
