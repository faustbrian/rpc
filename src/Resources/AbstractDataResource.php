<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Resources;

use Illuminate\Support\Str;
use Override;
use Spatie\LaravelData\Data;

use function class_basename;
use function data_get;

/**
 * Base resource transformer for Spatie Laravel Data objects.
 *
 * Provides a resource implementation that wraps Spatie Laravel Data DTOs,
 * automatically deriving the resource type from the Data class name and
 * exposing the DTO's properties as resource attributes.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractDataResource extends AbstractResource
{
    /**
     * Creates a new data resource wrapping a Spatie Data object.
     *
     * @param Data $model Spatie Laravel Data object to wrap as a resource
     */
    public function __construct(
        private readonly Data $model,
    ) {}

    /**
     * Derives the resource type from the Data class name.
     *
     * Extracts the type by removing the 'Data' suffix and converting to singular form.
     * For example, 'UserData' becomes 'user', 'PostsData' becomes 'post'.
     *
     * @return string Derived resource type identifier
     */
    #[Override()]
    public function getType(): string
    {
        return Str::singular(Str::beforeLast(class_basename($this->model), 'Data'));
    }

    /**
     * Returns the resource identifier from the Data object's id property.
     *
     * @return string Resource identifier as string
     */
    #[Override()]
    public function getId(): string
    {
        return (string) data_get($this->model, 'id');
    }

    /**
     * Returns all Data object properties as resource attributes.
     *
     * @return array<string, mixed> Array representation of the Data object
     */
    #[Override()]
    public function getAttributes(): array
    {
        /** @var array<string, mixed> */
        return $this->model->toArray();
    }

    /**
     * Returns relationships for this resource.
     *
     * Data resources do not support relationships by default. Override this method
     * in subclasses to provide relationship data when needed.
     *
     * @return array<string, mixed> Empty array (no relationships)
     */
    #[Override()]
    public function getRelations(): array
    {
        return [];
    }
}
