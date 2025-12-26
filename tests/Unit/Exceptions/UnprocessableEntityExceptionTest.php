<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\SemanticValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

describe('UnprocessableEntityException', function (): void {
    describe('Happy Paths', function (): void {
        test('creates exception with default detail', function (): void {
            $requestException = SemanticValidationException::create();

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_000);
            expect($requestException->getErrorMessage())->toBe('Server error');
            expect($requestException->getStatusCode())->toBe(422);
        });

        test('creates exception with custom detail', function (): void {
            $requestException = SemanticValidationException::create('Email format is invalid');

            expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
            expect($requestException->toArray())->toMatchSnapshot();
            expect($requestException->getErrorCode())->toBe(-32_000);
            expect($requestException->getErrorMessage())->toBe('Server error');
            expect($requestException->getStatusCode())->toBe(422);
        });

        test('creates exception from Laravel validation exception', function (): void {
            $validator = Validator::make(
                ['email' => 'invalid-email', 'age' => 'not-a-number'],
                ['email' => ['required', 'email'], 'age' => ['required', 'integer']],
            );

            try {
                $validator->validate();
            } catch (ValidationException $validationException) {
                $requestException = SemanticValidationException::createFromValidationException($validationException);

                expect($requestException)->toBeInstanceOf(AbstractRequestException::class);
                expect($requestException->toArray())->toMatchSnapshot();
                expect($requestException->getErrorCode())->toBe(-32_000);
                expect($requestException->getErrorMessage())->toBe('Unprocessable Entity');
                expect($requestException->getStatusCode())->toBe(422);
            }
        });
    });
});
