<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

/**
 * Represents the complete result of a JSON-RPC request execution.
 *
 * Encapsulates the full response including data payload, HTTP status code,
 * and response headers. Used as the final output container for both single
 * and batch requests, providing all information needed to construct the
 * HTTP response sent back to the client.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RequestResultData extends AbstractData
{
    /**
     * Create a new request result instance.
     *
     * @param mixed                 $data       The response payload containing either a single method
     *                                          result/error or an array of results for batch requests.
     *                                          Structure varies based on whether the request succeeded
     *                                          (MethodResultData), failed (MethodErrorData), or was batched
     *                                          (array of result/error objects).
     * @param int                   $statusCode HTTP status code for the response. Typically 200 for
     *                                          successful requests, 4xx for client errors (invalid request,
     *                                          method not found), or 5xx for server errors (parse errors,
     *                                          internal failures). Follows JSON-RPC over HTTP conventions.
     * @param array<string, string> $headers    Optional HTTP response headers to include
     *                                          in the response. May contain content-type,
     *                                          cache control, CORS headers, or custom
     *                                          application-specific headers.
     */
    public function __construct(
        public readonly mixed $data,
        public readonly int $statusCode,
        public readonly array $headers = [],
    ) {}
}
