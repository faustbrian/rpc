<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Transformers;

use Cline\RPC\Contracts\ResourceInterface;
use Cline\RPC\Data\DocumentData;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Data\ResourceObjectData;
use Cline\RPC\Normalizers\ModelNormalizer;
use Cline\RPC\Normalizers\ResourceNormalizer;
use Cline\RPC\QueryBuilders\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Transforms Eloquent models and resources into standardized JSON-RPC document structures.
 *
 * Provides methods for transforming single items, collections, and paginated results
 * into JSON-RPC response documents. Supports cursor pagination, offset pagination,
 * and simple pagination with metadata for page navigation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class Transformer
{
    /**
     * Create a new transformer instance.
     *
     * @param RequestObjectData $requestObject The JSON-RPC request containing pagination
     *                                         parameters and other transformation options.
     *                                         Used to extract page size, cursor, and page
     *                                         number from request parameters.
     */
    private function __construct(
        private RequestObjectData $requestObject,
    ) {}

    /**
     * Create a new transformer instance for the given request.
     *
     * Factory method providing a cleaner API for instantiating transformers with
     * the necessary request context for pagination and transformation.
     *
     * @param  RequestObjectData $requestObject Request containing transformation parameters
     * @return self              New transformer instance
     */
    public static function create(RequestObjectData $requestObject): self
    {
        return new self($requestObject);
    }

    /**
     * Transform a single model or resource into a JSON-RPC document.
     *
     * Normalizes the item using either ModelNormalizer for Eloquent models or
     * ResourceNormalizer for resource objects, ensuring consistent JSON-RPC
     * document structure regardless of the input type.
     *
     * @param  Model|ResourceInterface $item Model or resource to transform
     * @return DocumentData            JSON-RPC document containing the transformed item
     */
    public function item(Model|ResourceInterface $item): DocumentData
    {
        if ($item instanceof Model) {
            return DocumentData::from([
                'data' => ModelNormalizer::normalize($item)->toArray(),
            ]);
        }

        return DocumentData::from([
            'data' => ResourceNormalizer::normalize($item)->toArray(),
        ]);
    }

    /**
     * Transform a collection of models or resources into a JSON-RPC document.
     *
     * Iterates through the collection, normalizing each item based on its type,
     * and returns a document with all transformed items in the data array.
     *
     * @param  Collection<int, Model|ResourceInterface> $collection Collection of items to transform
     * @return DocumentData                             JSON-RPC document containing the transformed collection
     */
    public function collection(Collection $collection): DocumentData
    {
        return DocumentData::from([
            'data' => $collection->map(function (Model|ResourceInterface $item): ResourceObjectData {
                if ($item instanceof Model) {
                    return ModelNormalizer::normalize($item);
                }

                return ResourceNormalizer::normalize($item);
            })->toArray(),
        ]);
    }

    /**
     * Execute a cursor-paginated query and transform results into a JSON-RPC document.
     *
     * Applies cursor-based pagination to the query using parameters from the request
     * (page.size and page.cursor). Includes pagination metadata with cursor values
     * for navigating to previous/next pages. Cursor pagination is efficient for
     * large datasets as it doesn't require counting total records.
     *
     * @param  Builder|QueryBuilder $query Query builder to paginate and transform
     * @return DocumentData         JSON-RPC document with data array and pagination metadata
     */
    public function cursorPaginate(Builder|QueryBuilder $query): DocumentData
    {
        /** @var CursorPaginator $paginator */
        $paginator = $query->cursorPaginate(
            (int) $this->requestObject->getParam('page.size', '100'),
            ['*'],
            'page[cursor]',
            (string) $this->requestObject->getParam('page.cursor'),
        );

        $document = self::collection($paginator->getCollection())->toArray();

        if ($paginator->hasPages()) {
            $document['meta'] = [
                'page' => [
                    'cursor' => [
                        'self' => $paginator->cursor()?->encode(),
                        'prev' => $paginator->previousCursor()?->encode(),
                        'next' => $paginator->nextCursor()?->encode(),
                    ],
                ],
            ];
        }

        return DocumentData::from($document);
    }

    /**
     * Execute an offset-paginated query and transform results into a JSON-RPC document.
     *
     * Applies traditional offset-based pagination using page number and size from
     * the request (page.number and page.size). Includes pagination metadata with
     * current, previous, and next page numbers. This method counts total records,
     * making it suitable for scenarios requiring total page count display.
     *
     * @param  Builder|QueryBuilder $query Query builder to paginate and transform
     * @return DocumentData         JSON-RPC document with data array and page number metadata
     */
    public function paginate(Builder|QueryBuilder $query): DocumentData
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(
            (int) $this->requestObject->getParam('page.size', '100'),
            ['*'],
            'page[number]',
            (int) $this->requestObject->getParam('page.number'),
        );

        $document = self::collection($paginator->getCollection())->toArray();

        if ($paginator->hasPages()) {
            $document['meta'] = [
                'page' => [
                    'number' => [
                        'self' => $paginator->currentPage(),
                        'prev' => $paginator->onFirstPage() ? null : $paginator->currentPage() - 1,
                        'next' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                    ],
                ],
            ];
        }

        return DocumentData::from($document);
    }

    /**
     * Execute a simple paginated query and transform results into a JSON-RPC document.
     *
     * Applies lightweight pagination that only determines if more pages exist without
     * counting total records. Uses page number and size from the request. This is
     * more performant than paginate() for large datasets when total count isn't needed.
     *
     * @param  Builder|QueryBuilder $query Query builder to paginate and transform
     * @return DocumentData         JSON-RPC document with data array and basic page metadata
     */
    public function simplePaginate(Builder|QueryBuilder $query): DocumentData
    {
        /** @var Paginator $paginator */
        $paginator = $query->simplePaginate(
            (int) $this->requestObject->getParam('page.size', '100'),
            ['*'],
            'page[number]',
            (int) $this->requestObject->getParam('page.number'),
        );

        $document = self::collection($paginator->getCollection())->toArray();

        if ($paginator->hasPages()) {
            $document['meta'] = [
                'page' => [
                    'number' => [
                        'self' => $paginator->currentPage(),
                        'prev' => $paginator->onFirstPage() ? null : $paginator->currentPage() - 1,
                        'next' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                    ],
                ],
            ];
        }

        return DocumentData::from($document);
    }
}
