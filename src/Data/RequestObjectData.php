<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Represents a single JSON-RPC 2.0 request object.
 *
 * Encapsulates a complete JSON-RPC method invocation including protocol
 * version, unique identifier, method name, and optional parameters.
 * Supports both standard requests (with ID) and notifications (without ID).
 * Provides factory methods for creating properly formatted request objects
 * and utilities for parameter access.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.jsonrpc.org/specification#request_object
 */
final class RequestObjectData extends AbstractData
{
    /**
     * Create a new JSON-RPC request object instance.
     *
     * @param string                    $jsonrpc JSON-RPC protocol version identifier, must be exactly
     *                                           "2.0" to comply with the JSON-RPC 2.0 specification.
     * @param mixed                     $id      Request identifier for correlating requests and responses.
     *                                           May be a string, number, or null. When null, the request
     *                                           is treated as a notification that does not expect a response.
     * @param string                    $method  Name of the RPC method to invoke. Method names are
     *                                           case-sensitive and follow the implementation's naming
     *                                           conventions (e.g., "user.create", "getBalance").
     * @param null|array<string, mixed> $params  Optional method parameters as an associative
     *                                           array (named parameters) or indexed array (positional
     *                                           parameters). Structure depends on the method's signature
     *                                           and may be null if the method requires no parameters.
     */
    public function __construct(
        public readonly string $jsonrpc,
        public readonly mixed $id,
        public readonly string $method,
        public readonly ?array $params,
    ) {}

    /**
     * Create a standard JSON-RPC request that expects a response.
     *
     * Factory method for creating request objects with automatically generated
     * identifiers. Generates a ULID identifier if none is provided, ensuring
     * unique request tracking across distributed systems.
     *
     * @param  string                    $method Name of the RPC method to invoke
     * @param  null|array<string, mixed> $params Optional method parameters
     * @param  mixed                     $id     Optional custom request identifier. If null, a ULID will be generated
     * @return self                      Configured request object ready for transmission
     */
    public static function asRequest(string $method, ?array $params = null, mixed $id = null): self
    {
        return self::from([
            'jsonrpc' => '2.0',
            'id' => $id ?? Str::ulid(),
            'method' => $method,
            'params' => $params,
        ]);
    }

    /**
     * Create a JSON-RPC notification that does not expect a response.
     *
     * Factory method for creating notification requests. Notifications are
     * fire-and-forget operations where the client does not expect or wait
     * for a response from the server. Useful for logging, events, or updates
     * where response confirmation is not needed.
     *
     * @param  string                    $method Name of the RPC method to invoke
     * @param  null|array<string, mixed> $params Optional method parameters
     * @return self                      Configured notification object with null ID
     */
    public static function asNotification(string $method, ?array $params = null): self
    {
        return self::from([
            'jsonrpc' => '2.0',
            'id' => null,
            'method' => $method,
            'params' => $params,
        ]);
    }

    /**
     * Retrieve a specific parameter value using dot notation.
     *
     * Provides convenient access to nested parameter values using Laravel's
     * dot notation syntax (e.g., "user.email" for nested structures).
     * Returns a default value if the parameter is not found.
     *
     * @param  string $key     Parameter key in dot notation (e.g., "user.email")
     * @param  mixed  $default Default value to return if parameter is not found
     * @return mixed  The parameter value or the default value
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        if ($this->params === null) {
            return $default;
        }

        return Arr::get($this->params, $key, $default);
    }

    /**
     * Retrieve all parameters as an array.
     *
     * Returns the complete parameters array or null if no parameters were
     * provided with the request. Useful for methods that need to process
     * or validate all parameters at once.
     *
     * @return null|array<string, mixed> Complete parameters array or null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * Determine if this request is a notification.
     *
     * Notifications are JSON-RPC requests without an ID that do not expect
     * a response. This method checks for the presence of a null ID value
     * to identify notification-type requests.
     *
     * @return bool True if this is a notification (no response expected)
     */
    public function isNotification(): bool
    {
        return $this->id === null;
    }
}
