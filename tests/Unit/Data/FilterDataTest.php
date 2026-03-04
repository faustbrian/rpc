<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\FilterData;

describe('FilterData', function (): void {
    test('creates instance from array', function (): void {
        $data = FilterData::from([
            'attribute' => 'name',
            'condition' => 'eq',
            'value' => 'test',
        ]);
        expect($data)->toBeInstanceOf(FilterData::class);
    });
});
