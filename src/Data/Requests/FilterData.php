<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data\Requests;

use Cline\RPC\Data\AbstractData;

/**
 * Represents a single filter criterion for query operations.
 *
 * This data object encapsulates the parameters required to filter query results
 * based on attribute values, comparison operators, and boolean logic operators.
 * Used in list request operations to build complex query filtering conditions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class FilterData extends AbstractData
{
    /**
     * Create a new filter data instance.
     *
     * @param string      $attribute the name of the attribute/field to filter on, such as 'status',
     *                               'created_at', or any other model attribute that should be included
     *                               in the query filter criteria
     * @param string      $value     The value to compare against for the filter condition. This is the
     *                               expected value that the attribute should match based on the operator.
     *                               Values are passed as strings and may be cast to appropriate types
     *                               during query execution.
     * @param string      $operator  The comparison operator to apply when filtering, such as '=', '!=',
     *                               '>', '<', '>=', '<=', 'like', 'in', or other supported operators.
     *                               Determines how the attribute value is compared to the filter value.
     * @param null|string $boolean   The boolean logic operator ('and' or 'or') used to combine this filter
     *                               with adjacent filters in a filter collection. Null for the first filter
     *                               or when no chaining logic is needed. Controls query clause grouping.
     */
    public function __construct(
        public readonly string $attribute,
        public readonly string $value,
        public readonly string $operator,
        public readonly ?string $boolean,
    ) {}
}
