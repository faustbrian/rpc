<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

use function in_array;

/**
 * Represents a JSON-RPC 2.0 error response.
 *
 * Encapsulates error information according to the JSON-RPC 2.0 specification,
 * including error codes, messages, and optional additional data. Provides
 * utility methods to classify errors as client-side or server-side and
 * convert error codes to appropriate HTTP status codes.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.jsonrpc.org/specification#error_object
 */
final class ErrorData extends AbstractData
{
    /**
     * Create a new JSON-RPC error data instance.
     *
     * @param int    $code    Error code indicating the type of error that occurred.
     *                        Standard JSON-RPC error codes range from -32768 to -32000,
     *                        with specific ranges reserved for predefined errors (-32700 to -32600)
     *                        and server implementation errors (-32099 to -32000).
     * @param string $message Human-readable error message providing a concise description
     *                        of the error. Should be limited to a single sentence summary.
     * @param mixed  $data    Optional additional error information that may include structured
     *                        data, stack traces, or context-specific details to aid debugging.
     *                        The structure is implementation-defined and may be null.
     */
    public function __construct(
        public readonly int $code,
        public readonly string $message,
        public readonly mixed $data = null,
    ) {}

    /**
     * Determine if the error originated from client-side issues.
     *
     * Client errors indicate problems with the request itself, such as
     * malformed JSON-RPC structure, unknown methods, or invalid parameters.
     * These errors are typically in the -32600 to -32602 range.
     *
     * @return bool True if the error code indicates a client-side error
     */
    public function isClient(): bool
    {
        return in_array($this->code, [
            -32_600, // Invalid Request
            -32_601, // Method not found
            -32_602, // Invalid params
        ], true);
    }

    /**
     * Determine if the error originated from server-side issues.
     *
     * Server errors indicate problems during request processing on the server,
     * including JSON parsing failures (-32700), internal errors (-32603),
     * or implementation-defined server errors (-32099 to -32000).
     *
     * @return bool True if the error code indicates a server-side error
     */
    public function isServer(): bool
    {
        // Invalid JSON was received by the server.
        if ($this->code === -32_700) {
            return true;
        }

        // Internal JSON-RPC error.
        if ($this->code === -32_603) {
            return true;
        }

        // Reserved for implementation-defined server-errors.
        return $this->code >= -32_099 && $this->code <= -32_000;
    }

    /**
     * Convert the JSON-RPC error code to an appropriate HTTP status code.
     *
     * Maps JSON-RPC error codes to their corresponding HTTP status codes
     * following the JSON-RPC over HTTP specification. Client errors map
     * to 4xx codes while server errors map to 5xx codes.
     *
     * @see https://www.jsonrpc.org/historical/json-rpc-over-http.html#id19
     * @return int HTTP status code (200, 400, 404, or 500)
     */
    public function toStatusCode(): int
    {
        return match (true) {
            $this->code === -32_700 => 500, // Parse error.
            $this->code === -32_600 => 400, // Invalid Request.
            $this->code === -32_601 => 404, // Method not found.
            $this->code === -32_602 => 500, // Invalid params.
            $this->code === -32_603 => 500, // Internal error.
            $this->isServer() => 500,
            $this->isClient() => 400,
            default => 200,
        };
    }
}
