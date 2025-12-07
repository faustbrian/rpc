<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Methods;

use Cline\OpenRpc\ContentDescriptor\CursorPaginatorContentDescriptor;
use Cline\OpenRpc\ContentDescriptor\FieldsContentDescriptor;
use Cline\OpenRpc\ContentDescriptor\FiltersContentDescriptor;
use Cline\OpenRpc\ContentDescriptor\RelationshipsContentDescriptor;
use Cline\OpenRpc\ContentDescriptor\SortsContentDescriptor;
use Cline\RPC\Contracts\ResourceInterface;
use Cline\RPC\Data\DocumentData;
use Override;

/**
 * Base class for JSON-RPC list methods with cursor pagination support.
 *
 * Provides standardized list endpoint functionality with cursor pagination,
 * field selection, filtering, relationship loading, and sorting capabilities.
 * Automatically generates OpenRPC parameter descriptors from resource configuration.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractListMethod extends AbstractMethod
{
    /**
     * Handle the list request and return paginated results.
     *
     * Builds a query using the resource class, applies request filters and parameters,
     * and returns cursor-paginated results wrapped in a JSON API document structure.
     *
     * @return DocumentData The paginated resource collection with metadata
     */
    public function handle(): DocumentData
    {
        return $this->cursorPaginate(
            $this->query(
                $this->getResourceClass(),
            ),
        );
    }

    /**
     * Get the OpenRPC parameter descriptors for the list method.
     *
     * Generates standard list endpoint parameters including pagination, field selection,
     * filters, relationship inclusion, and sorting based on the resource class configuration.
     *
     * @return array<int, \Cline\OpenRpc\ContentDescriptor\ContentDescriptorInterface> Array of parameter descriptors
     */
    #[Override()]
    public function getParams(): array
    {
        $className = $this->getResourceClass();

        return [
            CursorPaginatorContentDescriptor::create(),
            FieldsContentDescriptor::create($className::getFields()),
            FiltersContentDescriptor::create($className::getFilters()),
            RelationshipsContentDescriptor::create($className::getRelationships()),
            SortsContentDescriptor::create($className::getSorts()),
        ];
    }

    /**
     * Get the resource class that defines available fields, filters, and relationships.
     *
     * @return class-string<ResourceInterface> The resource class name
     */
    abstract protected function getResourceClass(): string;
}
