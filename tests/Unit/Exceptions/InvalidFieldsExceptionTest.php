<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\InvalidFieldsException;

describe('InvalidFieldsException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates an invalid fields exception', function (): void {
            $requestException = InvalidFieldsException::create(['field1'], ['field2', 'field3']);

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_602);
            expect($requestException->getErrorMessage())->toBe('Invalid params');
        });
    });
});
