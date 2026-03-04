<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\Requests\SortData;

describe('SortData', function (): void {
    test('creates instance from array', function (): void {
        $data = SortData::from([
            'attribute' => 'created_at',
            'direction' => 'desc',
        ]);
        expect($data)->toBeInstanceOf(SortData::class)
            ->and($data->attribute)->toBe('created_at')
            ->and($data->direction)->toBe('desc');
    });
});
