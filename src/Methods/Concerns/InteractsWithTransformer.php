<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Methods\Concerns;

use Cline\RPC\Contracts\ResourceInterface;
use Cline\RPC\Data\DocumentData;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\QueryBuilders\QueryBuilder;
use Cline\RPC\Transformers\Transformer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Provides data transformation helpers for JSON-RPC methods.
 *
 * Offers convenient methods for transforming Eloquent models, collections,
 * and paginated results into JSON API-compliant document structures with
 * automatic field selection, relationship loading, and metadata inclusion.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @property RequestObjectData $requestObject
 */
trait InteractsWithTransformer
{
    /**
     * Transform a single model or resource into a JSON API document.
     *
     * Converts an Eloquent model or resource instance into a JSON API-compliant
     * document structure with field selection and relationship loading based on
     * the request parameters.
     *
     * @param  Model|ResourceInterface $item The model or resource to transform
     * @return DocumentData            The JSON API document containing the transformed item
     */
    protected function item(Model|ResourceInterface $item): DocumentData
    {
        return Transformer::create($this->requestObject)->item($item);
    }

    /**
     * Transform a collection of models into a JSON API document.
     *
     * Converts a collection of Eloquent models into a JSON API-compliant document
     * structure with field selection and relationship loading applied to all items.
     *
     * @param  Collection<int, Model> $collection The collection of models to transform
     * @return DocumentData           The JSON API document containing the transformed collection
     */
    protected function collection(Collection $collection): DocumentData
    {
        return Transformer::create($this->requestObject)->collection($collection);
    }

    /**
     * Execute cursor pagination and transform results into a JSON API document.
     *
     * Applies cursor-based pagination to the query and transforms the results
     * into a JSON API document with pagination metadata and links. Cursor pagination
     * provides efficient navigation for large datasets without offset performance issues.
     *
     * @param  Builder|QueryBuilder $query The query to paginate
     * @return DocumentData         The JSON API document with paginated results and cursor metadata
     */
    protected function cursorPaginate(Builder|QueryBuilder $query): DocumentData
    {
        return Transformer::create($this->requestObject)->cursorPaginate($query);
    }

    /**
     * Execute offset pagination and transform results into a JSON API document.
     *
     * Applies traditional offset-based pagination to the query and transforms
     * the results into a JSON API document with pagination metadata including
     * current page, total pages, and per-page count.
     *
     * @param  Builder|QueryBuilder $query The query to paginate
     * @return DocumentData         The JSON API document with paginated results and page metadata
     */
    protected function paginate(Builder|QueryBuilder $query): DocumentData
    {
        return Transformer::create($this->requestObject)->paginate($query);
    }

    /**
     * Execute simple pagination and transform results into a JSON API document.
     *
     * Applies simple pagination without total count calculation for better performance.
     * Only provides next/previous links without total page information, ideal for
     * large datasets where count queries are expensive.
     *
     * @param  Builder|QueryBuilder $query The query to paginate
     * @return DocumentData         The JSON API document with paginated results and basic navigation links
     */
    protected function simplePaginate(Builder|QueryBuilder $query): DocumentData
    {
        return Transformer::create($this->requestObject)->simplePaginate($query);
    }
}
