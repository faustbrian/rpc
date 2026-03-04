<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Illuminate\Support\Arr;

use function array_diff;
use function implode;
use function sprintf;

/**
 * Exception thrown when requested sort attributes are not permitted for resource ordering.
 *
 * Represents JSON-RPC error code -32602 for invalid sort specifications. This exception
 * prevents unauthorized sorting operations and ensures clients only sort by attributes
 * that are explicitly allowed, protecting against performance issues from unindexed
 * sorts and potential data exposure through sort-based attacks.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidSortsException extends AbstractRequestException
{
    /**
     * Creates an invalid sorts exception with detailed error information.
     *
     * Extracts sort attributes from the provided sort configurations, validates them
     * against the allowed sort set, and generates a JSON-RPC compliant error response.
     * The error details which sort attributes are not permitted and provides the
     * complete list of allowed sorts to guide clients in constructing valid queries.
     *
     * @param  array<int, array{attribute: string, direction?: string}> $unknownSorts Array of
     *                                                                                sort objects
     *                                                                                containing
     *                                                                                'attribute'
     *                                                                                and optional
     *                                                                                'direction'
     *                                                                                keys. The
     *                                                                                attributes
     *                                                                                are extracted
     *                                                                                and compared
     *                                                                                against
     *                                                                                allowed sorts
     *                                                                                to identify
     *                                                                                unauthorized
     *                                                                                sort attempts.
     * @param  array<int, string>                                       $allowedSorts Array of attribute names that are permitted for
     *                                                                                sorting. Used to validate client requests and
     *                                                                                generate helpful error messages showing valid
     *                                                                                sorting options for optimal query performance.
     * @return self                                                     a new instance with HTTP 422 status, JSON Pointer to /params/sorts,
     *                                                                  detailed error message comparing requested vs allowed sort attributes,
     *                                                                  and meta information containing both unknown and allowed sort lists
     */
    public static function create(array $unknownSorts, array $allowedSorts): self
    {
        $unknownSorts = Arr::pluck($unknownSorts, 'attribute');
        $unknownSorts = implode(', ', array_diff($unknownSorts, $allowedSorts));

        $allowedSorts = implode(', ', $allowedSorts);

        return self::new(-32_602, 'Invalid params', [
            [
                'status' => '422',
                'source' => ['pointer' => '/params/sorts'],
                'title' => 'Invalid sorts',
                'detail' => sprintf('Requested sorts `%s` is not allowed. Allowed sorts are `%s`.', $unknownSorts, $allowedSorts),
                'meta' => [
                    'unknown' => $unknownSorts,
                    'allowed' => $allowedSorts,
                ],
            ],
        ]);
    }
}
