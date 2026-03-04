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
 * Represents a JSON-RPC 2.0 error response for a method invocation.
 *
 * Encapsulates a complete error response structure according to the
 * JSON-RPC 2.0 specification, including protocol version, request
 * identifier, and detailed error information. Used when a method
 * invocation fails due to validation, processing, or server errors.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.jsonrpc.org/specification#response_object
 */
final class MethodErrorData extends AbstractData
{
    /**
     * Create a new method error response instance.
     *
     * @param string    $jsonrpc JSON-RPC protocol version identifier, must be exactly
     *                           "2.0" to comply with the JSON-RPC 2.0 specification.
     * @param mixed     $id      Request identifier that matches the original request, used
     *                           for correlating responses with their corresponding requests.
     *                           May be a string, number, or null for notification requests.
     * @param ErrorData $error   detailed error information including error code,
     *                           human-readable message, and optional additional data
     *                           to help diagnose and resolve the error condition
     */
    public function __construct(
        public readonly string $jsonrpc,
        public readonly mixed $id,
        public readonly ErrorData $error,
    ) {}

    /**
     * Convert the error response to an array representation.
     *
     * Transforms the error response into a JSON-RPC 2.0 compliant array
     * structure suitable for JSON serialization and transmission to clients.
     *
     * @return array{jsonrpc: string, id: mixed, error: ErrorData}
     */
    #[Override()]
    public function toArray(): array
    {
        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
            'error' => $this->error,
        ];
    }
}
