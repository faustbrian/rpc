<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

/**
 * Represents a filter criterion for data queries.
 *
 * Encapsulates a single filtering condition consisting of an attribute
 * to filter on, a comparison condition, and the value to compare against.
 * Used to build complex query filters for JSON-RPC method parameters.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class FilterData extends AbstractData
{
    /**
     * Create a new filter data instance.
     *
     * @param string $attribute the name of the attribute or field to filter on,
     *                          typically corresponding to a model property or database
     *                          column that will be used in query construction
     * @param string $condition the comparison operator or condition type to apply,
     *                          such as "equals", "greater_than", "contains", or other
     *                          implementation-defined operators for query filtering
     * @param mixed  $value     The value to compare against the attribute using the
     *                          specified condition. Type varies based on the attribute
     *                          being filtered (string, int, array, etc.) and the condition
     *                          operator being applied.
     */
    public function __construct(
        public readonly string $attribute,
        public readonly string $condition,
        public readonly mixed $value,
    ) {}
}
