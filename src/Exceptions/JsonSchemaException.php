<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use function sprintf;

/**
 * Exception thrown when JSON Schema validation rules are unsupported or invalid.
 *
 * Represents JSON-RPC error code -32603 for internal errors related to JSON Schema
 * validation configuration. This exception indicates a server-side configuration
 * issue where an unsupported validation rule is referenced, typically during schema
 * compilation or validation setup rather than actual request validation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class JsonSchemaException extends AbstractRequestException
{
    /**
     * Creates a JSON Schema exception for an unsupported validation rule.
     *
     * Generates a JSON-RPC compliant error response indicating that a referenced
     * validation rule is not supported by the JSON Schema validator. This is a
     * server configuration error rather than a client error, using HTTP 418 status
     * to distinguish it from typical validation failures.
     *
     * @param  string $rule The name of the unsupported JSON Schema validation rule that
     *                      was referenced in the schema definition. This typically occurs
     *                      when custom or extension rules are used without proper validator
     *                      configuration, or when referencing deprecated schema features.
     * @return self   a new instance with JSON-RPC error code -32603 (Internal error),
     *                HTTP 418 status (I'm a teapot - indicating misconfiguration), JSON
     *                Pointer to root (/), and a detailed message identifying the specific
     *                unsupported rule for server-side debugging and correction
     */
    public static function invalidRule(string $rule): self
    {
        return self::new(-32_603, 'Internal error', [
            [
                'status' => '418',
                'source' => ['pointer' => '/'],
                'title' => 'Invalid JSON Schema',
                'detail' => sprintf("The '%s' rule is not supported.", $rule),
            ],
        ]);
    }
}
