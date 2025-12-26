<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\Errors\SourceData;

describe('SourceData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates instance with pointer', function (): void {
            $data = SourceData::from([
                'pointer' => '/data/attributes/email',
            ]);

            expect($data)->toBeInstanceOf(SourceData::class)
                ->and($data->pointer)->toBe('/data/attributes/email');
        });

        test('creates instance with parameter', function (): void {
            $data = SourceData::from([
                'parameter' => 'userId',
            ]);

            expect($data)->toBeInstanceOf(SourceData::class)
                ->and($data->parameter)->toBe('userId');
        });

        test('creates instance with header', function (): void {
            $data = SourceData::from([
                'header' => 'Authorization',
            ]);

            expect($data)->toBeInstanceOf(SourceData::class)
                ->and($data->header)->toBe('Authorization');
        });
    });
});
