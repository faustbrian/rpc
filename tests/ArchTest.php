<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

arch('globals')
    ->expect(['dd', 'dump'])
    ->not->toBeUsed();

// arch('Cline\RPC\Clients')
//     ->expect('Cline\RPC\Clients')
//     ->toUseStrictTypes()
//     ->toBeFinal();

// arch('Cline\RPC\Contracts')
//     ->expect('Cline\RPC\Contracts')
//     ->toUseStrictTypes()
//     ->toBeInterfaces();

// arch('Cline\RPC\Data')
//     ->expect('Cline\RPC\Data')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->ignoring([
//         Cline\RPC\Data\AbstractContentDescriptorData::class,
//         Cline\RPC\Data\AbstractData::class,
//     ])
//     ->toHaveSuffix('Data')
//     ->toExtend(Spatie\LaravelData\Data::class);

// arch('Cline\RPC\Exceptions')
//     ->expect('Cline\RPC\Exceptions')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->ignoring([
//         Cline\RPC\Exceptions\AbstractRequestException::class,
//         Cline\RPC\Exceptions\Concerns\RendersThrowable::class,
//     ]);

// arch('Cline\RPC\Facades')
//     ->expect('Cline\RPC\Facades')
//     ->toUseStrictTypes()
//     ->toBeFinal();

// arch('Cline\RPC\Http')
//     ->expect('Cline\RPC\Http')
//     ->toUseStrictTypes()
//     ->toBeFinal();

// arch('Cline\RPC\Jobs')
//     ->expect('Cline\RPC\Jobs')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->toBeReadonly();

// arch('Cline\RPC\Methods')
//     ->expect('Cline\RPC\Methods')
//     ->toUseStrictTypes();

// arch('Cline\RPC\Mixins')
//     ->expect('Cline\RPC\Mixins')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->toBeReadonly();

// arch('Cline\RPC\Normalizers')
//     ->expect('Cline\RPC\Normalizers')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->toBeReadonly()
//     ->toHaveSuffix('Normalizer');

// arch('Cline\RPC\QueryBuilders')
//     ->expect('Cline\RPC\QueryBuilders')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->ignoring('Cline\RPC\QueryBuilders\Concerns');

// arch('Cline\RPC\Repositories')
//     ->expect('Cline\RPC\Repositories')
//     ->toUseStrictTypes()
//     ->toBeFinal();

// arch('Cline\RPC\Requests')
//     ->expect('Cline\RPC\Requests')
//     ->toUseStrictTypes()
//     ->toBeFinal();

// arch('Cline\RPC\Rules')
//     ->expect('Cline\RPC\Rules')
//     ->toUseStrictTypes()
//     ->toBeFinal();

// arch('Cline\RPC\Servers')
//     ->expect('Cline\RPC\Servers')
//     ->toUseStrictTypes()
//     ->toBeAbstract()
//     ->ignoring(ConfigurationServer::class);

// arch('Cline\RPC\Transformers')
//     ->expect('Cline\RPC\Transformers')
//     ->toUseStrictTypes()
//     ->toBeFinal()
//     ->toHaveSuffix('Transformer');
