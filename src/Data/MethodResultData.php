<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

use Override;

/**
 * Represents a successful JSON-RPC 2.0 method response.
 *
 * Encapsulates a complete success response structure according to the
 * JSON-RPC 2.0 specification, including protocol version, request
 * identifier, and the method's result data. Used when a method
 * invocation completes successfully without errors.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.jsonrpc.org/specification#response_object
 */
final class MethodResultData extends AbstractData
{
    /**
     * Create a new method result response instance.
     *
     * @param string $jsonrpc JSON-RPC protocol version identifier, must be exactly
     *                        "2.0" to comply with the JSON-RPC 2.0 specification.
     * @param mixed  $id      Request identifier that matches the original request, used
     *                        for correlating responses with their corresponding requests.
     *                        May be a string, number, or null for notification requests.
     * @param mixed  $result  The method's successful return value, which may be any
     *                        JSON-serializable data type including primitives, arrays,
     *                        or objects. Structure is determined by the specific method
     *                        implementation and API contract.
     */
    public function __construct(
        public readonly string $jsonrpc,
        public readonly mixed $id,
        public readonly mixed $result,
    ) {}

    /**
     * Convert the success response to an array representation.
     *
     * Transforms the success response into a JSON-RPC 2.0 compliant array
     * structure suitable for JSON serialization and transmission to clients.
     *
     * @return array{jsonrpc: string, id: mixed, result: mixed}
     */
    #[Override()]
    public function toArray(): array
    {
        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
            'result' => $this->result,
        ];
    }
}
