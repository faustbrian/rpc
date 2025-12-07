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
 * Represents a JSON:API compliant error object.
 *
 * Provides structured error information following the JSON:API error object
 * specification, including unique identifiers, status codes, detailed messages,
 * source information, and extensible metadata for comprehensive error reporting.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://jsonapi.org/format/#error-objects
 */
final class ErrorObjectData extends AbstractData
{
    /**
     * Create a new JSON:API error object instance.
     *
     * @param string                    $id     unique identifier for this specific error occurrence,
     *                                          used for error tracking, logging, and support ticket
     *                                          correlation across distributed systems
     * @param null|LinksData            $links  optional hypermedia links providing additional
     *                                          resources about the error, such as documentation
     *                                          URLs or type definition references
     * @param string                    $status HTTP status code applicable to this error, represented
     *                                          as a string value (e.g., "400", "500") to maintain
     *                                          JSON:API specification compliance.
     * @param string                    $code   application-specific error code for programmatic error
     *                                          handling, typically more granular than HTTP status codes
     *                                          and consistent across API versions
     * @param string                    $title  short, human-readable summary of the error type that
     *                                          remains consistent across occurrences of the same error,
     *                                          suitable for display in user interfaces
     * @param string                    $detail human-readable explanation specific to this error
     *                                          occurrence, providing context and actionable information
     *                                          to help resolve the issue
     * @param null|SourceData           $source optional reference to the specific part of the
     *                                          request that caused the error, including JSON
     *                                          pointers, parameter names, or header references
     * @param null|array<string, mixed> $meta   optional extensible metadata object containing
     *                                          non-standard information about the error, such as
     *                                          stack traces, request IDs, or debugging context
     */
    public function __construct(
        public readonly string $id,
        public readonly ?LinksData $links,
        public readonly string $status,
        public readonly string $code,
        public readonly string $title,
        public readonly string $detail,
        public readonly ?SourceData $source,
        public readonly ?array $meta,
    ) {}
}
