<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\Requests\FilterData;

describe('FilterData', function (): void {
    test('creates instance from array', function (): void {
        $data = FilterData::from([
            'attribute' => 'status',
            'value' => 'active',
            'operator' => 'eq',
            'boolean' => null,
        ]);
        expect($data)->toBeInstanceOf(FilterData::class)
            ->and($data->attribute)->toBe('status')
            ->and($data->operator)->toBe('eq');
    });
});
