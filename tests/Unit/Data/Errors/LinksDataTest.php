<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\Errors\LinksData;

describe('LinksData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates instance from array', function (): void {
            $data = LinksData::from([
                'about' => 'https://example.com/docs/errors',
                'type' => 'https://example.com/types/validation',
            ]);

            expect($data)->toBeInstanceOf(LinksData::class)
                ->and($data->about)->toBe('https://example.com/docs/errors');
        });
    });
});
