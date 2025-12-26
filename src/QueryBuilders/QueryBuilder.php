<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\QueryBuilders;

use Cline\RPC\Exceptions\InvalidFieldsException;
use Cline\RPC\Exceptions\InvalidFiltersException;
use Cline\RPC\Exceptions\InvalidRelationshipsException;
use Cline\RPC\Exceptions\InvalidSortsException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Traits\ForwardsCalls;

use function array_key_exists;
use function in_array;
use function throw_unless;

/**
 * Builds Eloquent query instances with validated filtering, sorting, and field selection.
 *
 * This query builder acts as a wrapper around Laravel's Eloquent Builder, providing
 * type-safe query construction with validation of requested fields, filters, relationships,
 * and sorts against configured allow-lists. It supports complex filtering operations,
 * eager loading with constraints, and selective field retrieval.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @mixin Builder
 */
final class QueryBuilder
{
    use ForwardsCalls;

    /**
     * Validated fields to select, indexed by resource name.
     *
     * @var array<string, array<int, string>>
     */
    private array $queryFields = [];

    /**
     * Validated filter conditions, indexed by resource name.
     *
     * @var array<string, array<int, array{attribute: string, operator: string, value: mixed, boolean?: string}>>
     */
    private array $queryFilters = [];

    /**
     * Validated relationships to eager load, indexed by resource name.
     *
     * @var array<string, array<int, string>>
     */
    private array $queryRelationships = [];

    /**
     * Validated sort directives, indexed by resource name.
     *
     * @var array<string, array<int, array{attribute: string, direction: string}>>
     */
    private array $querySorts = [];

    /**
     * Fully qualified model class name.
     *
     * @var class-string
     */
    private readonly string $model;

    /**
     * Database table name for the primary model.
     */
    private readonly string $modelTable;

    /**
     * Resource type identifier for the primary model.
     */
    private readonly string $modelType;

    /**
     * Underlying Eloquent query builder instance.
     */
    private readonly Builder $subject;

    /**
     * Creates a new query builder with validation of all query parameters.
     *
     * @param class-string                                                                                          $resource             Resource class providing model configuration
     * @param array<string, array<int, string>>                                                                     $requestFields        Requested field selections by resource
     * @param array<string, array<int, string>>                                                                     $allowedFields        Allowed field selections by resource
     * @param array<string, array<int, array{attribute: string, operator: string, value: mixed, boolean?: string}>> $requestFilters       Requested filter conditions by resource
     * @param array<string, array<int, string>>                                                                     $allowedFilters       Allowed filter attributes by resource
     * @param array<string, array<int, string>>                                                                     $requestRelationships Requested relationships by resource
     * @param array<string, array<int, string>>                                                                     $allowedRelationships Allowed relationships by resource
     * @param array<string, array<int, array{attribute: string, direction: string}>>                                $requestSorts         Requested sort directives by resource
     * @param array<string, array<int, string>>                                                                     $allowedSorts         Allowed sort attributes by resource
     *
     * @throws InvalidFieldsException        When requested fields are not in the allow-list
     * @throws InvalidFiltersException       When requested filters are not in the allow-list
     * @throws InvalidRelationshipsException When requested relationships are not in the allow-list
     * @throws InvalidSortsException         When requested sorts are not in the allow-list
     */
    private function __construct(
        private readonly string $resource,
        private readonly array $requestFields,
        private readonly array $allowedFields,
        private readonly array $requestFilters,
        private readonly array $allowedFilters,
        private readonly array $requestRelationships,
        private readonly array $allowedRelationships,
        private readonly array $requestSorts,
        private readonly array $allowedSorts,
    ) {
        $this->model = $resource::getModel();
        $this->modelTable = $resource::getModelTable();
        $this->modelType = $resource::getModelType();
        $this->subject = $resource::getModel()::query();

        $this->collectFields();
        $this->collectFilters();
        $this->collectRelationships();
        $this->collectSorts();

        $this->applyToQuery();
    }

    /**
     * Forwards method calls to the underlying Eloquent builder.
     *
     * Enables method chaining by proxying calls to the wrapped Builder instance.
     * Returns $this for chainable methods to maintain fluent interface.
     *
     * @param  string            $name      Method name to call on the builder
     * @param  array<int, mixed> $arguments Arguments to pass to the method
     * @return mixed             Result from the forwarded method call, or $this for chaining
     */
    public function __call(string $name, array $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        // If the forwarded method call is part of a chain we can return $this
        // instead of the actual $result to keep the chain going.
        if ($result === $this->subject) {
            return $this;
        }

        return $result;
    }

    /**
     * Creates a new query builder instance with validated parameters.
     *
     * Factory method that validates all requested query parameters against their
     * corresponding allow-lists and constructs a configured Eloquent query.
     *
     * @param class-string                                                                                          $resource             Resource class providing model configuration
     * @param array<string, array<int, string>>                                                                     $requestFields        Requested field selections by resource
     * @param array<string, array<int, string>>                                                                     $allowedFields        Allowed field selections by resource
     * @param array<string, array<int, array{attribute: string, operator: string, value: mixed, boolean?: string}>> $requestFilters       Requested filter conditions by resource
     * @param array<string, array<int, string>>                                                                     $allowedFilters       Allowed filter attributes by resource
     * @param array<string, array<int, string>>                                                                     $requestRelationships Requested relationships by resource
     * @param array<string, array<int, string>>                                                                     $allowedRelationships Allowed relationships by resource
     * @param array<string, array<int, array{attribute: string, direction: string}>>                                $requestSorts         Requested sort directives by resource
     * @param array<string, array<int, string>>                                                                     $allowedSorts         Allowed sort attributes by resource
     *
     * @throws InvalidFieldsException        When requested fields are not in the allow-list
     * @throws InvalidFiltersException       When requested filters are not in the allow-list
     * @throws InvalidRelationshipsException When requested relationships are not in the allow-list
     * @throws InvalidSortsException         When requested sorts are not in the allow-list
     *
     * @return static Configured query builder instance
     */
    public static function for(
        string $resource,
        array $requestFields,
        array $allowedFields,
        array $requestFilters,
        array $allowedFilters,
        array $requestRelationships,
        array $allowedRelationships,
        array $requestSorts,
        array $allowedSorts,
    ): static {
        return new self(
            $resource,
            $requestFields,
            $allowedFields,
            $requestFilters,
            $allowedFilters,
            $requestRelationships,
            $allowedRelationships,
            $requestSorts,
            $allowedSorts,
        );
    }

    /**
     * Validates and collects requested fields against the allow-list.
     *
     * @throws InvalidFieldsException When a requested field is not in the allow-list
     */
    private function collectFields(): void
    {
        foreach ($this->requestFields as $resourceName => $resourceFields) {
            foreach ($resourceFields as $resourceField) {
                $allowedFields = $this->allowedFields[$resourceName] ?? [];

                throw_unless(in_array($resourceField, $allowedFields, true), InvalidFieldsException::create($resourceFields, $allowedFields));
            }

            $this->queryFields[$resourceName] = $resourceFields;
        }
    }

    /**
     * Validates and collects requested filters against the allow-list.
     *
     * @throws InvalidFiltersException When a filter attribute is not in the allow-list
     */
    private function collectFilters(): void
    {
        foreach ($this->requestFilters as $resourceName => $resourceFilters) {
            foreach ($resourceFilters as $resourceFilter) {
                $attribute = $resourceFilter['attribute'] ?? null;
                $allowedFilters = $this->allowedFilters[$resourceName] ?? [];

                throw_unless(in_array($attribute, $allowedFilters, true), InvalidFiltersException::create([$attribute], $allowedFilters));

                $this->queryFilters[$resourceName][] = $resourceFilter;
            }
        }
    }

    /**
     * Validates and collects requested relationships against the allow-list.
     *
     * @throws InvalidRelationshipsException When a relationship is not in the allow-list
     */
    private function collectRelationships(): void
    {
        foreach ($this->requestRelationships as $resourceName => $relationships) {
            foreach ($relationships as $relationship) {
                $allowedRelationships = $this->allowedRelationships[$resourceName] ?? [];

                throw_unless(in_array($relationship, $allowedRelationships, true), InvalidRelationshipsException::create(
                    $this->requestRelationships[$resourceName],
                    $allowedRelationships,
                ));
            }
        }

        $this->queryRelationships = $this->requestRelationships;
    }

    /**
     * Validates and collects requested sorts against the allow-list.
     *
     * @throws InvalidSortsException When a sort attribute is not in the allow-list
     */
    private function collectSorts(): void
    {
        foreach ($this->requestSorts as $resourceName => $resourceSorts) {
            foreach ($resourceSorts as $resourceSort) {
                $allowedSorts = $this->allowedSorts[$resourceName] ?? [];

                throw_unless(in_array($resourceSort['attribute'], $allowedSorts, true), InvalidSortsException::create($resourceSorts, $allowedSorts));

                $this->querySorts[$resourceName][] = $resourceSort;
            }
        }
    }

    /**
     * Applies all collected query parameters to the Eloquent builder.
     *
     * Constructs the final query by applying relationships, field selections, filters,
     * and sorts in the correct order. Handles both base model queries and nested
     * relationship queries with proper constraint closures.
     */
    private function applyToQuery(): void
    {
        // Arrange...
        $withs = [];

        // Relationships...
        foreach ($this->queryRelationships as $relationshipResource => $relationships) {
            foreach ($relationships as $relationship) {
                if ($relationshipResource === 'self') {
                    $withs[$relationship] = fn (Builder|Relation $query): Builder|Relation => $query;
                } elseif (array_key_exists($relationshipResource, $withs)) {
                    $withs[$relationship] = fn (Builder|Relation $query) => $withs[$relationshipResource]($query)->with($relationship);
                } else {
                    $withs[$relationship] = fn (Builder|Relation $query) => $query->with($relationship);
                }
            }
        }

        // Fields...
        foreach ($this->queryFields as $fieldResource => $fields) {
            if ($fieldResource === 'self') {
                $this->select($fields);
            } elseif (array_key_exists($fieldResource, $withs)) {
                $withs[$fieldResource] = fn (Builder|Relation $query) => $withs[$fieldResource]($query)->select($fields);
            } else {
                $withs[$fieldResource] = fn (Builder|Relation $query) => $query->select($fields);
            }
        }

        // Filters...
        foreach ($this->queryFilters as $filterResource => $filters) {
            $filterRelationships = [];

            foreach ($filters as $filter) {
                if ($filterResource === 'self') {
                    $this->applyFilter($this, $filter);
                } elseif (array_key_exists($filterResource, $filterRelationships)) {
                    $filterRelationships[$filterResource] = $this->applyFilter($filterRelationships[$filterResource], $filter);
                } else {
                    $filterRelationships[$filterResource] = fn (Builder|Relation $query): Builder|Relation|QueryBuilder => $this->applyFilter($query, $filter);
                }
            }

            foreach ($filterRelationships as $filterRelationshipName => $filterRelationshipQuery) {
                $this->whereHas($filterRelationshipName, $filterRelationshipQuery);
            }
        }

        // Sorts...
        foreach ($this->querySorts as $sortResource => $sorts) {
            foreach ($sorts as $sort) {
                if ($sortResource === 'self') {
                    $this->orderBy($sort['attribute'], $sort['direction']);
                } elseif (array_key_exists($sortResource, $withs)) {
                    $withs[$sortResource] = fn (Builder|Relation $query) => $withs[$sortResource]($query)->orderBy($sort['attribute'], $sort['direction']);
                } else {
                    $withs[$sortResource] = fn (Builder|Relation $query) => $query->orderBy($sort['attribute'], $sort['direction']);
                }
            }
        }

        // Act...
        $this->with($withs);
    }

    /**
     * Applies a single filter condition to a query builder.
     *
     * Maps filter operators to their corresponding Eloquent query methods and applies
     * the filter with the specified attribute, value, and boolean operator (AND/OR).
     * Supports standard comparison, pattern matching, range, and null check operators.
     *
     * @param Builder|Relation|self                                                      $query  Query builder instance to apply the filter to
     * @param array{attribute: string, operator: string, value: mixed, boolean?: string} $filter Filter configuration
     *
     * @throws InvalidFiltersException When the operator is not recognized
     *
     * @return Builder|Relation|self Modified query builder with filter applied
     */
    private function applyFilter(Builder|Relation|self $query, array $filter): Builder|Relation|self
    {
        $attribute = $filter['attribute'] ?? null;
        $value = $filter['value'] ?? null;
        $boolean = $filter['boolean'] ?? 'and';

        match ($filter['operator'] ?? null) {
            'equals' => $query->where($attribute, '=', $value, $boolean),
            'not_equals' => $query->where($attribute, '!=', $value, $boolean),
            'greater_than' => $query->where($attribute, '>', $value, $boolean),
            'greater_than_or_equal_to' => $query->where($attribute, '>=', $value, $boolean),
            'less_than' => $query->where($attribute, '<', $value, $boolean),
            'less_than_or_equal_to' => $query->where($attribute, '<=', $value, $boolean),
            'like' => $query->where($attribute, 'like', $value, $boolean),
            'not_like' => $query->where($attribute, 'not like', $value, $boolean),
            'in' => $query->whereIn($attribute, $value, $boolean),
            'not_in' => $query->whereNotIn($attribute, $value, $boolean),
            'between' => $query->whereBetween($attribute, $value, $boolean),
            'not_between' => $query->whereNotBetween($attribute, $value, $boolean),
            'is_null' => $query->whereNull($attribute, $boolean),
            'is_not_null' => $query->whereNotNull($attribute, $boolean),
            default => throw InvalidFiltersException::create([$attribute], $this->allowedFilters),
        };

        return $query;
    }
}
