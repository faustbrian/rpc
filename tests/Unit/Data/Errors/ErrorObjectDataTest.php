<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\Errors\ErrorObjectData;
use Cline\RPC\Data\Errors\LinksData;
use Cline\RPC\Data\Errors\SourceData;

describe('ErrorObjectData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates instance with all fields', function (): void {
            $data = ErrorObjectData::from([
                'id' => 'error-123',
                'links' => LinksData::from(['about' => 'https://example.com/error']),
                'status' => '400',
                'code' => 'INVALID_REQUEST',
                'title' => 'Invalid Request',
                'detail' => 'The request was malformed',
                'source' => SourceData::from(['pointer' => '/data/attributes']),
                'meta' => ['timestamp' => '2024-01-01'],
            ]);

            expect($data)->toBeInstanceOf(ErrorObjectData::class)
                ->and($data->status)->toBe('400')
                ->and($data->title)->toBe('Invalid Request');
        });

        test('creates instance with minimal fields', function (): void {
            $data = ErrorObjectData::from([
                'id' => 'err-500',
                'links' => null,
                'status' => '500',
                'code' => 'INTERNAL_ERROR',
                'title' => 'Internal Server Error',
                'detail' => 'An error occurred',
                'source' => null,
                'meta' => null,
            ]);

            expect($data)->toBeInstanceOf(ErrorObjectData::class)
                ->and($data->status)->toBe('500')
                ->and($data->code)->toBe('INTERNAL_ERROR');
        });
    });
});
