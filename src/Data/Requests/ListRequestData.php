<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data\Requests;

use Cline\RPC\Data\AbstractData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

/**
 * Represents a comprehensive list/index request with query parameters.
 *
 * This data object encapsulates all parameters needed for paginated list requests,
 * including field selection (sparse fieldsets), filtering, relationship inclusion,
 * and sorting. Follows JSON:API conventions for resource collection queries.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ListRequestData extends AbstractData
{
    /**
     * Create a new list request data instance.
     *
     * @param null|array<string, array<int, string>> $fields        Sparse fieldsets specification mapping resource
     *                                                              types to arrays of field names to include in the
     *                                                              response. Allows clients to request only specific
     *                                                              attributes, reducing payload size and improving
     *                                                              performance for large resource collections.
     * @param null|DataCollection<int, FilterData>   $filters       Collection of filter criteria to apply to the query,
     *                                                              with each FilterData object defining an attribute,
     *                                                              operator, value, and boolean logic for combining
     *                                                              filters. Enables complex query filtering with
     *                                                              multiple conditions and logical operators.
     * @param null|array<int, string>                $relationships Array of relationship names to include in the response
     *                                                              via eager loading. Reduces N+1 query problems by
     *                                                              loading related resources in a single request.
     *                                                              Follows JSON:API include parameter conventions.
     * @param null|DataCollection<int, SortData>     $sorts         Collection of sort criteria defining the ordering
     *                                                              of results, with each SortData object specifying
     *                                                              an attribute and direction (ascending/descending).
     *                                                              Multiple sort criteria are applied in sequence.
     */
    public function __construct(
        public readonly ?array $fields,
        #[DataCollectionOf(FilterData::class)]
        public readonly ?DataCollection $filters,
        public readonly ?array $relationships,
        #[DataCollectionOf(SortData::class)]
        public readonly ?DataCollection $sorts,
    ) {}
}
