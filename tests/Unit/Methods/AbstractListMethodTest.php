<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Repositories\ResourceRepository;
use Tests\Support\Fakes\Methods\ListUsers;
use Tests\Support\Models\User;
use Tests\Support\Resources\UserResource;

describe('AbstractListMethod', function (): void {
    beforeEach(function (): void {
        ResourceRepository::register(User::class, UserResource::class);
    });

    test('creates list method instance', function (): void {
        $method = new ListUsers();

        expect($method)->toBeInstanceOf(ListUsers::class);
        expect($method->getName())->toBe('users.list');
    });

    test('handles list request', function (): void {
        User::query()->create(['name' => 'John Doe', 'created_at' => now(), 'updated_at' => now()]);
        User::query()->create(['name' => 'Jane Doe', 'created_at' => now(), 'updated_at' => now()]);

        $method = new ListUsers();
        $request = RequestObjectData::from([
            'jsonrpc' => '2.0',
            'method' => 'users.list',
            'id' => 1,
        ]);

        $method->setRequest($request);
        $result = $method->handle();

        expect($result)->toBeObject();
        expect($result->data)->toBeArray();
    });

    test('gets params configuration', function (): void {
        $method = new ListUsers();
        $params = $method->getParams();

        expect($params)->toBeArray();
        expect($params)->not()->toBeEmpty();
    });
});
