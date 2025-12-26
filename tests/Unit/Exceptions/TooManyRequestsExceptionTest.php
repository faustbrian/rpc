<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\TooManyRequestsException;

describe('TooManyRequestsException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates exception with default detail', function (): void {
            $requestException = TooManyRequestsException::create();

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_000);
            expect($requestException->getErrorMessage())->toBe('Server error');
            expect($requestException->getStatusCode())->toBe(429);
        });

        test('creates exception with custom detail', function (): void {
            $requestException = TooManyRequestsException::create('Rate limit: 100 requests per minute');

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_000);
            expect($requestException->getErrorMessage())->toBe('Server error');
            expect($requestException->getStatusCode())->toBe(429);
        });
    });
});
