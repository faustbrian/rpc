<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Normalizers;

use Cline\RPC\Contracts\ResourceInterface;
use Cline\RPC\Data\ResourceObjectData;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Converts resource objects into normalized resource object structures.
 *
 * This normalizer transforms ResourceInterface instances into ResourceObjectData by
 * converting the resource's attributes and recursively normalizing relationships.
 * Unlike ModelNormalizer, this operates on resource objects directly and uses
 * naming conventions to detect relationship cardinality.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ResourceNormalizer
{
    /**
     * Normalizes a resource object into a resource object structure.
     *
     * Converts the resource's data and recursively processes any defined relationships.
     * Relationship cardinality is determined by name pluralization: singular names indicate
     * one-to-one relationships, while plural names indicate one-to-many relationships.
     *
     * @param  ResourceInterface  $resource The resource instance to normalize
     * @return ResourceObjectData Normalized resource object containing type, id, attributes, and relationships
     */
    public static function normalize(ResourceInterface $resource): ResourceObjectData
    {
        $pendingResourceObject = $resource->toArray();

        foreach ($resource->getRelations() as $relationName => $relationModels) {
            // Detect relationship cardinality by checking if the relation name is singular
            $isOneToOne = Str::plural($relationName) !== $relationName;

            if ($isOneToOne) {
                $relationModels = Arr::wrap($relationModels);
            }

            /** @var ResourceInterface $relationship */
            foreach ($relationModels as $relationship) {
                if ($isOneToOne) {
                    $pendingResourceObject['relationships'][$relationName] = $relationship;
                } else {
                    $pendingResourceObject['relationships'][$relationName][] = $relationship;
                }
            }
        }

        return ResourceObjectData::from($pendingResourceObject);
    }
}
