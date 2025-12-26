<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\ExceptionMapper;
use Cline\RPC\Exceptions\InternalErrorException;

describe('ExceptionMapper', function (): void {
    test('maps exception to JSON-RPC exception', function (): void {
        $exception = new Exception('Test error');
        $mapped = ExceptionMapper::execute($exception);

        expect($mapped)->toBeInstanceOf(InternalErrorException::class);
    });
});
