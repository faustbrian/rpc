<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Normalizers;

use Cline\RPC\Data\ResourceObjectData;
use Cline\RPC\Repositories\ResourceRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Converts Eloquent models into normalized resource object structures.
 *
 * This normalizer transforms Laravel Eloquent models into ResourceObjectData instances
 * by resolving the appropriate resource transformer, converting the model's attributes,
 * and recursively normalizing any loaded relationships while preserving their cardinality.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ModelNormalizer
{
    /**
     * Normalizes an Eloquent model into a resource object structure.
     *
     * Resolves the model's resource transformer from the ResourceRepository, converts
     * the model's attributes, and recursively processes any eager-loaded relationships.
     * The relationship cardinality (one-to-one vs one-to-many) is automatically detected
     * and preserved in the output structure.
     *
     * @param  Model              $model The Eloquent model instance to normalize
     * @return ResourceObjectData Normalized resource object containing type, id, attributes, and relationships
     */
    public static function normalize(Model $model): ResourceObjectData
    {
        $resource = ResourceRepository::get($model);
        $pendingResourceObject = $resource->toArray();

        foreach ($resource->getRelations() as $relationName => $relationModels) {
            if ($relationModels === null) {
                continue;
            }

            // Detect relationship cardinality by checking if the relation returns a single model
            $isOneToOne = $relationModels instanceof Model;

            if ($isOneToOne) {
                $relationModels = Arr::wrap($relationModels);
            }

            /** @var Model $relationModel */
            foreach ($relationModels as $relationModel) {
                $relationship = ResourceRepository::get($relationModel)->toArray();

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
