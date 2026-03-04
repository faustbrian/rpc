<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use RuntimeException;

/**
 * Exception thrown when attempting to register a method name that already exists.
 *
 * Prevents duplicate method registration in the MethodRepository, ensuring
 * each method name is unique within the registry.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodAlreadyRegisteredException extends RuntimeException implements RpcException
{
    /**
     * Create exception for duplicate method registration.
     *
     * @param  string $methodName The method name that was already registered
     * @return self   The created exception instance
     */
    public static function forMethod(string $methodName): self
    {
        return new self('Method already registered: '.$methodName);
    }
}
