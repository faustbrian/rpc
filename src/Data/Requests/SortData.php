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
 * Represents a single sort criterion for query result ordering.
 *
 * This data object encapsulates the parameters required to sort query results
 * by a specific attribute in ascending or descending order. Used in list request
 * operations to control the ordering of returned resource collections.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SortData extends AbstractData
{
    /**
     * Create a new sort data instance.
     *
     * @param string $attribute The name of the attribute/field to sort by, such as 'created_at',
     *                          'name', or any other model attribute that should determine the
     *                          ordering of query results. The attribute must be sortable and
     *                          accessible within the query context.
     * @param string $direction The sort direction, typically 'asc' for ascending order or 'desc'
     *                          for descending order. Controls whether results are sorted from
     *                          lowest to highest values or highest to lowest values based on
     *                          the specified attribute.
     */
    public function __construct(
        public readonly string $attribute,
        public readonly string $direction,
    ) {}
}
