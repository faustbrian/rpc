<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use function array_diff;
use function implode;
use function sprintf;

/**
 * Exception thrown when requested fields are not permitted for sparse fieldset selection.
 *
 * Represents JSON-RPC error code -32602 for invalid field specifications in resource
 * queries. This exception is thrown when clients request fields that are not in the
 * allowed fieldset, preventing exposure of sensitive or unauthorized data through
 * sparse fieldset requests as per JSON:API conventions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidFieldsException extends AbstractRequestException
{
    /**
     * Creates an invalid fields exception with detailed error information.
     *
     * Compares requested fields against the allowed fieldset and generates a
     * JSON-RPC compliant error response detailing which fields are not permitted.
     * The error includes both unknown and allowed fields in the meta section for
     * client-side debugging and corrective action.
     *
     * @param  array<int, string> $unknownFields Array of field names that were requested but
     *                                           are not in the allowed fieldset. These will
     *                                           be compared against allowed fields to identify
     *                                           the specific unauthorized field requests.
     * @param  array<int, string> $allowedFields Array of field names that are permitted for
     *                                           sparse fieldset selection. Used to validate
     *                                           client requests and generate helpful error
     *                                           messages showing valid field options.
     * @return self               a new instance with HTTP 422 status, JSON Pointer to /params/fields,
     *                            detailed error message comparing requested vs allowed fields, and
     *                            meta information containing both unknown and allowed field lists
     */
    public static function create(array $unknownFields, array $allowedFields): self
    {
        $unknownFields = implode(', ', array_diff($unknownFields, $allowedFields));
        $allowedFields = implode(', ', $allowedFields);

        return self::new(-32_602, 'Invalid params', [
            [
                'status' => '422',
                'source' => ['pointer' => '/params/fields'],
                'title' => 'Invalid fields',
                'detail' => sprintf('Requested fields `%s` are not allowed. Allowed fields are `%s`.', $unknownFields, $allowedFields),
                'meta' => [
                    'unknown' => $unknownFields,
                    'allowed' => $allowedFields,
                ],
            ],
        ]);
    }
}
