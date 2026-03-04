<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Exceptions\MethodNotFoundException;
use Cline\RPC\Repositories\MethodRepository;
use Tests\Support\Fakes\Methods\Subtract;
use Tests\Support\Fakes\Methods\SubtractWithBinding;

describe('MethodRepository', function (): void {
    describe('Happy Paths', function (): void {
        test('registers and retrieves a method', function (): void {
            $methodRepository = new MethodRepository();
            $methodRepository->register(
                new Subtract(),
            );

            expect($methodRepository->get('app.subtract'))->toBeInstanceOf(Subtract::class);
        });

        test('registers a method using a class string', function (): void {
            $methodRepository = new MethodRepository();
            $methodRepository->register(Subtract::class);

            expect($methodRepository->get('app.subtract'))->toBeInstanceOf(MethodInterface::class);
        });

        test('retrieves all registered methods', function (): void {
            $methodRepository = new MethodRepository();
            $methodRepository->register(Subtract::class);
            $methodRepository->register(SubtractWithBinding::class);

            $methods = $methodRepository->all();

            expect($methods)->toHaveCount(2);
            expect($methods)->toHaveKey('app.subtract');
            expect($methods)->toHaveKey('app.subtract_with_binding');
        });
    });

    describe('Sad Paths', function (): void {
        test('throws an exception when a method is not found', function (): void {
            $methodRepository = new MethodRepository();

            $methodRepository->get('nonExistentMethod');
        })->throws(MethodNotFoundException::class);

        test('throws an exception when registering a duplicate method', function (): void {
            $methodRepository = new MethodRepository();
            $methodRepository->register(Subtract::class);
            $methodRepository->register(Subtract::class);
        })->throws(RuntimeException::class);
    });
});
