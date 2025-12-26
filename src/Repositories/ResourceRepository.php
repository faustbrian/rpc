<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Repositories;

use Cline\RPC\Contracts\ResourceInterface;
use Cline\RPC\Exceptions\InternalErrorException;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

use function sprintf;
use function throw_if;

/**
 * Static registry mapping Eloquent models to their resource transformers.
 *
 * Maintains a global mapping between model classes and their corresponding resource
 * transformer classes. This allows the system to dynamically resolve the correct
 * transformer when converting models to API resources.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ResourceRepository
{
    /**
     * Registered resource classes, indexed by model class name.
     *
     * @var array<class-string, class-string<ResourceInterface>>
     */
    private static array $resources = [];

    /**
     * Returns all registered model-to-resource mappings.
     *
     * @return array<class-string, class-string<ResourceInterface>> Array of mappings indexed by model class
     */
    public static function all(): array
    {
        return self::$resources;
    }

    /**
     * Retrieves and instantiates the resource transformer for a given model.
     *
     * Looks up the registered resource class for the model's type and creates
     * a new instance with the model as its source data.
     *
     * @param Model $model Model instance to get the resource transformer for
     *
     * @throws InternalErrorException When no resource is registered for the model class
     *
     * @return ResourceInterface Instantiated resource transformer wrapping the model
     */
    public static function get(Model $model): ResourceInterface
    {
        $resource = self::$resources[$model::class] ?? null;

        throw_if($resource === null, InternalErrorException::create(
            new DomainException(sprintf('Resource for model [%s] not found.', $model)),
        ));

        $resource = new $resource($model);

        if ($resource instanceof ResourceInterface) {
            return $resource;
        }

        throw InternalErrorException::create(
            new DomainException(sprintf('Resource for model [%s] not found.', $model)),
        );
    }

    /**
     * Removes a model-to-resource mapping from the registry.
     *
     * @param class-string $model Fully qualified model class name to remove
     */
    public static function forget(string $model): void
    {
        Arr::forget(self::$resources, $model);
    }

    /**
     * Registers a resource transformer class for a model.
     *
     * @param class-string                    $model    Fully qualified model class name
     * @param class-string<ResourceInterface> $resource Fully qualified resource class name
     */
    public static function register(string $model, string $resource): void
    {
        self::$resources[$model] = $resource;
    }
}
