<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\XmlRpcEncodingException;

describe('XmlRpcEncodingException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates request encoding exception', function (): void {
            // Arrange
            $previous = new RuntimeException('Encoding failed');

            // Act
            $exception = XmlRpcEncodingException::request($previous);

            // Assert
            expect($exception)->toBeInstanceOf(RuntimeException::class);
            expect($exception->getMessage())->toBe('XML-RPC request encoding failed');
            expect($exception->getCode())->toBe(0);
            expect($exception->getPrevious())->toBe($previous);
        });

        test('creates response encoding exception', function (): void {
            // Arrange
            $previous = new RuntimeException('XML generation failed');

            // Act
            $exception = XmlRpcEncodingException::response($previous);

            // Assert
            expect($exception)->toBeInstanceOf(RuntimeException::class);
            expect($exception->getMessage())->toBe('XML-RPC response encoding failed');
            expect($exception->getCode())->toBe(0);
            expect($exception->getPrevious())->toBe($previous);
        });
    });
});
