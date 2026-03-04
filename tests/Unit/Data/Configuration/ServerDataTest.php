<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\Configuration\ServerData;

describe('ServerData', function (): void {
    test('creates instance from array', function (): void {
        $data = ServerData::from([
            'name' => 'test',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => null,
            'content_descriptors' => [],
            'schemas' => [],
        ]);
        expect($data)->toBeInstanceOf(ServerData::class)
            ->and($data->name)->toBe('test')
            ->and($data->version)->toBe('1.0');
    });
});
