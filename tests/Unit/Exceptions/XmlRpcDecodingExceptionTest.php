<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\XmlRpcDecodingException;

describe('XmlRpcDecodingException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates request decoding exception', function (): void {
            // Arrange
            $previous = new RuntimeException('Invalid XML');

            // Act
            $exception = XmlRpcDecodingException::request($previous);

            // Assert
            expect($exception)->toBeInstanceOf(RuntimeException::class);
            expect($exception->getMessage())->toBe('XML-RPC request decoding failed');
            expect($exception->getCode())->toBe(0);
            expect($exception->getPrevious())->toBe($previous);
        });

        test('creates response decoding exception', function (): void {
            // Arrange
            $previous = new RuntimeException('Malformed XML');

            // Act
            $exception = XmlRpcDecodingException::response($previous);

            // Assert
            expect($exception)->toBeInstanceOf(RuntimeException::class);
            expect($exception->getMessage())->toBe('XML-RPC response decoding failed');
            expect($exception->getCode())->toBe(0);
            expect($exception->getPrevious())->toBe($previous);
        });
    });
});
