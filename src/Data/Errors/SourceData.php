<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data\Errors;

use Cline\RPC\Data\AbstractData;

/**
 * Represents the source location of an error in a JSON:API request.
 *
 * Identifies the specific part of the request that caused an error,
 * whether it's a field in the request body (via JSON Pointer), a
 * query parameter, or an HTTP header. This information aids debugging
 * by pinpointing exactly where validation or processing failed.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://jsonapi.org/format/#error-objects
 * @see https://datatracker.ietf.org/doc/html/rfc6901
 */
final class SourceData extends AbstractData
{
    /**
     * Create a new error source information object.
     *
     * @param null|string $pointer   JSON Pointer (RFC 6901) reference to the specific
     *                               field or location in the request document that caused
     *                               the error. Uses slash-separated path syntax like
     *                               "/data/attributes/email" for precise error location tracking.
     * @param null|string $parameter Name of the URL query string parameter that triggered
     *                               the error. Used to identify validation failures in query
     *                               parameters and provide specific feedback about invalid
     *                               input values in GET requests.
     * @param null|string $header    Name of the HTTP header that caused the error condition.
     *                               Commonly used for authentication failures (Authorization),
     *                               content negotiation issues (Content-Type, Accept), or
     *                               custom header validation failures in API requests.
     */
    public function __construct(
        public readonly ?string $pointer,
        public readonly ?string $parameter,
        public readonly ?string $header,
    ) {}
}
