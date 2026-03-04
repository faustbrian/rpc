<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\ServerNotFoundException;

describe('ServerNotFoundException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates a server not error exception', function (): void {
            $requestException = ServerNotFoundException::create();

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_099);
            expect($requestException->getErrorMessage())->toBe('Server not found');
        });
    });
});
