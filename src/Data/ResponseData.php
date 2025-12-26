<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

use Cline\RPC\Exceptions\AbstractRequestException;
use Override;

/**
 * Represents a JSON-RPC 2.0 compliant response object.
 *
 * This data object encapsulates all components of a JSON-RPC 2.0 response,
 * including the protocol version, request identifier, result data, and error
 * information. Provides factory methods for creating responses from exceptions
 * and notifications, along with helper methods for response status checks.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ResponseData extends AbstractData
{
    /**
     * Create a new response data instance.
     *
     * @param string         $jsonrpc The JSON-RPC protocol version identifier, must be '2.0' to comply
     *                                with the JSON-RPC 2.0 specification. This field is required in all
     *                                JSON-RPC responses to indicate protocol compliance.
     * @param mixed          $id      The request identifier that matches the original request's id value.
     *                                Allows clients to correlate responses with requests. Null for
     *                                notification responses where no reply is expected.
     * @param mixed          $result  The successful result data returned from the RPC method execution.
     *                                Only present when the request succeeds. Mutually exclusive with
     *                                the error property according to JSON-RPC 2.0 specification.
     * @param null|ErrorData $error   The error object containing error code, message, and optional data
     *                                when the request fails. Only present when an error occurs. Mutually
     *                                exclusive with the result property per JSON-RPC 2.0 specification.
     */
    public function __construct(
        public readonly string $jsonrpc,
        public readonly mixed $id = null,
        public readonly mixed $result = null,
        public readonly ?ErrorData $error = null,
    ) {}

    /**
     * Create a response from a request exception.
     *
     * Factory method that constructs an error response from an AbstractRequestException,
     * automatically extracting error details and formatting them according to JSON-RPC 2.0
     * error response specifications.
     *
     * @param  AbstractRequestException $exception the exception containing error code, message, and data
     *                                             to be transformed into a JSON-RPC error response
     * @return self                     a response data instance with error information and no result data
     */
    public static function createFromRequestException(AbstractRequestException $exception): self
    {
        return self::from([
            'jsonrpc' => '2.0',
            'error' => $exception->toError(),
        ]);
    }

    /**
     * Create a notification response.
     *
     * Factory method that constructs a minimal notification response with no id, result,
     * or error. Notifications in JSON-RPC 2.0 do not expect a response from the server,
     * so this creates an acknowledgment response when needed.
     *
     * @return self a minimal response data instance indicating a notification acknowledgment
     */
    public static function asNotification(): self
    {
        return self::from([
            'jsonrpc' => '2.0',
        ]);
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool true if no error occurred, false if the response contains error information
     */
    public function isSuccessful(): bool
    {
        return !$this->isFailed();
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     *
     * @return bool true if the response contains client or server error information
     */
    public function isFailed(): bool
    {
        if ($this->isServerError()) {
            return true;
        }

        return $this->isClientError();
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * Client errors typically indicate invalid requests, malformed JSON, or
     * method-specific validation failures that can be corrected by the client.
     *
     * @return bool true if the error is a client-side error (error code indicates client fault)
     */
    public function isClientError(): bool
    {
        if (!$this->error instanceof ErrorData) {
            return false;
        }

        return $this->error->isClient();
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * Server errors indicate internal failures, method implementation errors,
     * or other server-side issues that are not caused by client input.
     *
     * @return bool true if the error is a server-side error (error code indicates server fault)
     */
    public function isServerError(): bool
    {
        if (!$this->error instanceof ErrorData) {
            return false;
        }

        return $this->error->isServer();
    }

    /**
     * Determine if the request is a notification.
     *
     * Notifications are JSON-RPC requests that do not expect a response. This method
     * identifies notification acknowledgments by checking for the absence of id, result,
     * and error fields.
     *
     * @return bool true if this is a notification response (no id, result, or error present)
     */
    public function isNotification(): bool
    {
        return $this->id === null && $this->result === null && !$this->error instanceof ErrorData;
    }

    /**
     * Convert the response data to an array representation.
     *
     * Transforms the response into a JSON-RPC 2.0 compliant array structure,
     * ensuring that only appropriate fields are included based on whether the
     * response contains an error or a result. Error responses exclude the result
     * field, while success responses exclude the error field.
     *
     * @return array<string, mixed> The JSON-RPC 2.0 compliant response array.
     */
    #[Override()]
    public function toArray(): array
    {
        if (!$this->error instanceof ErrorData) {
            return [
                'jsonrpc' => $this->jsonrpc,
                'id' => $this->id,
                'result' => $this->result,
            ];
        }

        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
            'error' => $this->error,
        ];
    }
}
