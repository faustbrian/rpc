<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

/**
 * Document wrapper for JSON-RPC responses following JSON:API patterns.
 *
 * Provides a standardized top-level structure for RPC responses that mirrors
 * the JSON:API document format. This ensures consistent response shapes across
 * all RPC methods and facilitates client-side response handling.
 *
 * The document structure separates successful response data from error
 * information and optional metadata, following JSON:API conventions defined
 * at https://jsonapi.org/format/#document-top-level.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DocumentData extends AbstractData
{
    /**
     * Create a new JSON-RPC document response.
     *
     * @param array<string, mixed>      $data   Primary response payload containing the method's
     *                                          result data. For successful responses, this holds
     *                                          the actual return value from the RPC method. Should
     *                                          be structured according to the method's result
     *                                          content descriptor.
     * @param null|array<int, mixed>    $errors Optional array of error objects for failed
     *                                          requests. Each error should include code, message,
     *                                          and optional data fields. Null for successful
     *                                          responses. Following JSON:API, errors and data
     *                                          should not both be present.
     * @param null|array<string, mixed> $meta   Optional metadata object containing non-standard
     *                                          information about the response such as pagination
     *                                          details, timing information, or API version. Can
     *                                          be present with either successful or error responses.
     */
    public function __construct(
        public readonly array $data,
        public readonly ?array $errors = null,
        public readonly ?array $meta = null,
    ) {}
}
