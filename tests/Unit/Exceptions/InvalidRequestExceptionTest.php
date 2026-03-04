<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\StructurallyInvalidRequestException;

describe('InvalidRequestException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates an invalid request exception', function (): void {
            $requestException = StructurallyInvalidRequestException::create();

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_600);
            expect($requestException->getErrorMessage())->toBe('Invalid Request');
        });
    });
});
