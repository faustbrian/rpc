<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Resources;

use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\QueryBuilders\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Override;

use function array_keys;
use function class_basename;
use function resolve;
use function sprintf;

/**
 * Base resource class for transforming Eloquent models into JSON-RPC responses.
 *
 * Provides automatic model-to-resource transformation with support for field selection,
 * filtering, relationship loading, and sorting through the QueryBuilder. Automatically
 * determines the model type and table name based on the resource class name.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractModelResource extends AbstractResource
{
    /**
     * Create a new model resource instance.
     *
     * @param Model $model The Eloquent model instance to transform into a resource.
     *                     Provides the underlying data and relationships that will
     *                     be serialized according to the resource's field and
     *                     relationship configuration.
     */
    public function __construct(
        private readonly Model $model,
    ) {}

    /**
     * Create a new query builder instance for the resource.
     *
     * Constructs a configured QueryBuilder with allowed fields, filters, relationships,
     * and sorts extracted from the request parameters. The builder validates requested
     * operations against the resource's allowed configuration to prevent unauthorized
     * data access.
     *
     * @param  RequestObjectData $request The JSON-RPC request containing query parameters
     *                                    for fields, filters, relationships, and sorting
     * @return QueryBuilder      Configured query builder ready to execute database operations
     */
    public static function query(RequestObjectData $request): QueryBuilder
    {
        return QueryBuilder::for(
            resource: static::class,
            requestFields: (array) $request->getParam('fields', []),
            allowedFields: static::getFields(),
            requestFilters: (array) $request->getParam('filters', []),
            allowedFilters: static::getFilters(),
            requestRelationships: (array) $request->getParam('relationships', []),
            allowedRelationships: static::getRelationships(),
            requestSorts: (array) $request->getParam('sorts', []),
            allowedSorts: static::getSorts(),
        );
    }

    /**
     * Get the fully qualified class name of the associated Eloquent model.
     *
     * Automatically derives the model class name from the resource class name by
     * removing the "Resource" suffix and prepending the App\Models namespace.
     * For example, "UserResource" maps to "App\Models\User".
     *
     * @return string Fully qualified model class name (e.g., "App\Models\User")
     */
    public static function getModel(): string
    {
        return sprintf(
            'App\\Models\\%s',
            Str::beforeLast(class_basename(static::class), 'Resource'),
        );
    }

    /**
     * Get the database table name for the associated model.
     *
     * Instantiates the model and retrieves its configured table name from Eloquent.
     * This supports models with custom table names defined via the $table property.
     *
     * @return string Database table name (e.g., "users")
     */
    public static function getModelTable(): string
    {
        return resolve(static::getModel())->getTable();
    }

    /**
     * Get the resource type identifier based on the model's table name.
     *
     * Returns the singular form of the model's table name, which is used as the
     * resource type identifier in JSON-RPC responses. For example, "users" becomes "user".
     *
     * @return string Singular form of the table name (e.g., "user")
     */
    public static function getModelType(): string
    {
        return Str::singular(static::getModelTable());
    }

    /**
     * Get the resource type identifier for this instance.
     *
     * Returns the singular form of the model's table name as the resource type.
     * This value is used in the "type" field of JSON-RPC resource objects.
     *
     * @return string Singular form of the table name (e.g., "user")
     */
    #[Override()]
    public function getType(): string
    {
        return Str::singular(static::getModelTable());
    }

    /**
     * Get the unique identifier for this resource instance.
     *
     * Extracts the model's primary key value and casts it to a string for use
     * in JSON-RPC responses. Assumes the model uses "id" as the primary key.
     *
     * @return string String representation of the model's ID
     */
    #[Override()]
    public function getId(): string
    {
        // @phpstan-ignore-next-line
        return (string) $this->model->id;
    }

    /**
     * Get the resource attributes to be included in the JSON-RPC response.
     *
     * Extracts model attributes filtered by the resource's allowed fields configuration,
     * automatically excluding the "id" field and any relationship fields. Only returns
     * attributes that are defined in the resource's getFields() configuration.
     *
     * @return array<string, mixed> Filtered model attributes excluding ID and relations
     */
    #[Override()]
    public function getAttributes(): array
    {
        $rawAttributes = $this->model->toArray();

        $attributes = Arr::only($rawAttributes, static::getFields()['self']);

        Arr::forget($attributes, 'id');

        foreach (array_keys($this->getRelations()) as $relation) {
            if (!Arr::has($attributes, $relation)) {
                continue;
            }

            Arr::forget($attributes, $relation);
        }

        return $attributes;
    }

    /**
     * Get the loaded Eloquent relationships for this resource.
     *
     * Returns all relationships that have been eager-loaded on the model instance.
     * These relationships are typically loaded via the QueryBuilder's relationship
     * configuration and are included in the JSON-RPC response.
     *
     * @return array<string, mixed> Array of loaded relationship data keyed by relation name
     */
    #[Override()]
    public function getRelations(): array
    {
        return $this->model->getRelations();
    }
}
