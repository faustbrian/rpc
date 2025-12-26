<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\MethodNotFoundException;

describe('MethodNotFoundException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates a method not found exception', function (): void {
            $requestException = MethodNotFoundException::create();

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_601);
            expect($requestException->getErrorMessage())->toBe('Method not found');
        });
    });
});
