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
 * Exception thrown when requested filters are not permitted for resource queries.
 *
 * Represents JSON-RPC error code -32602 for invalid filter specifications. This
 * exception prevents unauthorized filtering operations and ensures clients only
 * apply filters that are explicitly allowed by the resource configuration,
 * protecting against data exposure and query injection attacks.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidFiltersException extends AbstractRequestException
{
    /**
     * Creates an invalid filters exception with detailed error information.
     *
     * Validates requested filters against the allowed filter set and generates a
     * JSON-RPC compliant error response. The error details which filters are not
     * permitted and provides the complete list of allowed filters to guide clients
     * in constructing valid queries.
     *
     * @param  array<int, string> $unknownFilters Array of filter names that were requested but
     *                                            are not in the allowed filter set. These will
     *                                            be compared against allowed filters to identify
     *                                            specific unauthorized filter attempts.
     * @param  array<int, string> $allowedFilters Array of filter names that are permitted for
     *                                            this resource. Used to validate client requests
     *                                            and generate helpful error messages showing
     *                                            valid filtering options.
     * @return self               a new instance with HTTP 422 status, JSON Pointer to /params/filters,
     *                            detailed error message comparing requested vs allowed filters, and
     *                            meta information containing both unknown and allowed filter lists
     */
    public static function create(array $unknownFilters, array $allowedFilters): self
    {
        $unknownFilters = implode(', ', array_diff($unknownFilters, $allowedFilters));
        $allowedFilters = implode(', ', $allowedFilters);

        return self::new(-32_602, 'Invalid params', [
            [
                'status' => '422',
                'source' => ['pointer' => '/params/filters'],
                'title' => 'Invalid filters',
                'detail' => sprintf('Requested filters `%s` are not allowed. Allowed filters are `%s`.', $unknownFilters, $allowedFilters),
                'meta' => [
                    'unknown' => $unknownFilters,
                    'allowed' => $allowedFilters,
                ],
            ],
        ]);
    }
}
