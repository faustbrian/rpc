<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Cline\RPC\Data\ErrorData;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

use function array_filter;

/**
 * Base exception class for JSON-RPC request errors.
 *
 * This abstract exception provides the foundation for all JSON-RPC error handling,
 * encapsulating error data and providing standardized error response formatting.
 * Extends PHP's Exception class to integrate with standard exception handling while
 * adding JSON-RPC specific error code and status code mapping.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractRequestException extends Exception implements RpcException
{
    /**
     * Create a new request exception instance.
     *
     * @param ErrorData $error The error data object containing code, message, and optional
     *                         additional data. This encapsulates all error information that
     *                         will be included in the JSON-RPC error response.
     */
    public function __construct(
        public readonly ErrorData $error,
    ) {
        parent::__construct(
            $this->getErrorMessage(),
            $this->getErrorCode(),
        );
    }

    /**
     * Get the JSON-RPC error code.
     *
     * @return int the error code from the error data object, or the exception code as fallback
     */
    public function getErrorCode(): int
    {
        return $this->error->code ?? $this->code;
    }

    /**
     * Get the error message.
     *
     * @return string the error message from the error data object, or the exception message as fallback
     */
    public function getErrorMessage(): string
    {
        return $this->error->message ?? $this->message;
    }

    /**
     * Get additional error data.
     *
     * @return mixed optional additional error information such as validation errors or debug data
     */
    public function getErrorData(): mixed
    {
        return $this->error->data;
    }

    /**
     * Get the HTTP status code for this error.
     *
     * Maps standard JSON-RPC error codes to appropriate HTTP status codes:
     * - Parse Error (-32700): 400 Bad Request
     * - Invalid Request (-32600): 400 Bad Request
     * - Method Not Found (-32601): 404 Not Found
     * - Invalid Params (-32602): 400 Bad Request
     * - Internal Error (-32603): 500 Internal Server Error
     * - All others: 500 Internal Server Error
     *
     * @return int the HTTP status code corresponding to the JSON-RPC error code
     */
    public function getStatusCode(): int
    {
        return match ($this->getErrorCode()) {
            -32_700 => 400,
            -32_600 => 400,
            -32_601 => 404,
            -32_602 => 400,
            -32_603 => 500,
            default => 500,
        };
    }

    /**
     * Get HTTP headers to include in the error response.
     *
     * @return array<string, string> Array of HTTP headers. Empty by default, may be overridden by subclasses.
     */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Convert the exception to an ErrorData object.
     *
     * @return ErrorData the error data representation of this exception
     */
    public function toError(): ErrorData
    {
        return ErrorData::from($this->toArray());
    }

    /**
     * Convert the exception to an array representation.
     *
     * Creates a JSON-RPC compliant error object array containing code, message,
     * and data. When debug mode is enabled, automatically includes file location,
     * line number, and stack trace in the error data for development purposes.
     *
     * @return array<string, mixed> the error array with filtered null values removed
     */
    public function toArray(): array
    {
        $message = [
            'code' => $this->getErrorCode(),
            'message' => $this->getErrorMessage(),
            'data' => $this->getErrorData(),
        ];

        if (App::hasDebugModeEnabled()) {
            Arr::set(
                $message,
                'data.debug',
                [
                    'file' => $this->getFile(),
                    'line' => $this->getLine(),
                    'trace' => $this->getTraceAsString(),
                ],
            );
        }

        return array_filter($message);
    }

    /**
     * Create a new exception instance with error details.
     *
     * Factory method for constructing exception instances with specific error code,
     * message, and optional data. Used by concrete exception classes to create
     * standardized error instances.
     *
     * @param  null|int                  $code    The JSON-RPC error code (e.g., -32600 for invalid request).
     * @param  null|string               $message the human-readable error message
     * @param  null|array<string, mixed> $data    optional additional error data such as validation details
     * @return static                    the constructed exception instance
     */
    protected static function new(?int $code, ?string $message, ?array $data = null): static
    {
        // @phpstan-ignore-next-line
        return new static(
            ErrorData::from([
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ]),
        );
    }
}
