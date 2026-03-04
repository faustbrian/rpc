<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\ServiceUnavailableException;

describe('ServiceUnavailableException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates exception with default detail', function (): void {
            $requestException = ServiceUnavailableException::create();

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_000);
            expect($requestException->getErrorMessage())->toBe('Server error');
            expect($requestException->getStatusCode())->toBe(503);
        });

        test('creates exception with custom detail', function (): void {
            $requestException = ServiceUnavailableException::create('Database maintenance in progress');

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_000);
            expect($requestException->getErrorMessage())->toBe('Server error');
            expect($requestException->getStatusCode())->toBe(503);
        });
    });
});
