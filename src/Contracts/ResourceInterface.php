<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Contracts;

/**
 * Contract for JSON-RPC resource transformers.
 *
 * Defines the interface for resource transformation objects that convert
 * domain models into standardized JSON representations for RPC responses.
 * Follows JSON:API-inspired patterns for consistent resource serialization.
 *
 * Resource transformers handle the mapping of model data to a structured
 * format including type identifiers, attributes, and relationship loading.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @method static array<int, string> getFields()        Get available field definitions for sparse fieldsets
 * @method static array<int, string> getFilters()       Get available filter criteria for resource queries
 * @method static array<int, string> getRelationships() Get available relationships that can be included
 * @method static array<int, string> getSorts()         Get available sort parameters for ordering results
 * @method static string             getModel()         Get the fully qualified model class name
 */
interface ResourceInterface
{
    /**
     * Get the resource type identifier.
     *
     * Returns a string identifier that categorizes this resource type.
     * Used for polymorphic relationships and client-side resource routing.
     * Should be plural and kebab-case (e.g., "blog-posts", "user-profiles").
     *
     * @return string Resource type identifier
     */
    public function getType(): string;

    /**
     * Get the primary identifier for this resource instance.
     *
     * Returns the unique identifier value (typically primary key) that
     * distinguishes this resource instance from others of the same type.
     * Must be a string representation even for numeric IDs.
     *
     * @return string Unique resource identifier
     */
    public function getId(): string;

    /**
     * Get all loaded attributes for this resource instance.
     *
     * Returns a key-value array of the resource's attributes excluding
     * the ID (which is handled separately) and relationships. Only includes
     * attributes that have been loaded or explicitly requested via sparse
     * fieldsets to avoid N+1 query problems.
     *
     * @return array<string, mixed> Resource attributes as key-value pairs
     */
    public function getAttributes(): array;

    /**
     * Get all loaded relationship data for this resource instance.
     *
     * Returns an array of related resource data that has been eagerly loaded
     * or explicitly included in the request. Relationships are represented
     * as nested resource objects or arrays of resource objects.
     *
     * @return array<string, mixed> Loaded relationship data indexed by relation name
     */
    public function getRelations(): array;

    /**
     * Transform the resource instance into an array representation.
     *
     * Serializes the complete resource including type, ID, attributes, and
     * relationships into a standardized array structure suitable for JSON
     * encoding and transmission in RPC responses.
     *
     * @return array<string, mixed> Complete resource array representation
     */
    public function toArray(): array;
}
