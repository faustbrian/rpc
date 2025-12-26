<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Methods\Concerns;

use Cline\RPC\Contracts\ResourceInterface;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\QueryBuilders\QueryBuilder;

/**
 * Provides query building helpers for JSON-RPC methods.
 *
 * Offers convenient methods for initializing resource query builders with
 * automatic parameter resolution from the JSON-RPC request object. Enables
 * filtering, sorting, and relationship loading based on request parameters.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @property RequestObjectData $requestObject
 */
trait InteractsWithQueryBuilder
{
    /**
     * Create a new query builder for a resource class.
     *
     * Initializes a QueryBuilder instance using the resource class's query
     * method, passing the current request object for automatic parameter
     * resolution including filters, sorts, fields, and relationships.
     *
     * @param  class-string<ResourceInterface> $class The resource class to query
     * @return QueryBuilder                    The configured query builder with request parameters applied
     */
    protected function query(string $class): QueryBuilder
    {
        return $class::query($this->requestObject);
    }
}
