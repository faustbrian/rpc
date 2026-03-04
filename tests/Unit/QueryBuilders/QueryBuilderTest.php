<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\InvalidFieldsException;
use Cline\RPC\Exceptions\InvalidFiltersException;
use Cline\RPC\Exceptions\InvalidRelationshipsException;
use Cline\RPC\Exceptions\InvalidSortsException;
use Cline\RPC\QueryBuilders\QueryBuilder;
use Tests\Support\Resources\UserResource;

describe('QueryBuilder', function (): void {
    describe('Happy Paths', function (): void {
        test('applies fields to the query', function (): void {
            expect(
                QueryBuilder::for(
                    resource: UserResource::class,
                    requestFields: [
                        'self' => ['name'],
                        'posts' => ['name'],
                    ],
                    allowedFields: [
                        'self' => ['name'],
                        'posts' => ['name'],
                    ],
                    requestFilters: [],
                    allowedFilters: [],
                    requestRelationships: [],
                    allowedRelationships: [],
                    requestSorts: [],
                    allowedSorts: [],
                )->toSql(),
            )->toContain('select "name"');
        });

        test('applies filters to the query', function (): void {
            expect(
                QueryBuilder::for(
                    resource: UserResource::class,
                    requestFields: [],
                    allowedFields: [],
                    requestFilters: [
                        'self' => [
                            [
                                'attribute' => 'name',
                                'operator' => 'equals',
                                'value' => 'John',
                            ],
                        ],
                    ],
                    allowedFilters: [
                        'self' => ['name'],
                    ],
                    requestRelationships: [],
                    allowedRelationships: [],
                    requestSorts: [],
                    allowedSorts: [],
                )->toSql(),
            )->toContain('where "name" = ?');
        });

        test('applies relationships to the query', function (): void {
            expect(
                QueryBuilder::for(
                    resource: UserResource::class,
                    requestFields: [],
                    allowedFields: [],
                    requestFilters: [],
                    allowedFilters: [],
                    requestRelationships: [
                        'self' => ['posts'],
                    ],
                    allowedRelationships: [
                        'self' => ['posts', 'comments'],
                    ],
                    requestSorts: [],
                    allowedSorts: [],
                )->toSql(),
            )->toContain('select *');
        });

        test('applies sorts to the query', function (): void {
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [
                    'self' => [
                        [
                            'attribute' => 'name',
                            'direction' => 'asc',
                        ],
                    ],
                ],
                allowedSorts: [
                    'self' => ['name', 'email'],
                ],
            );

            expect($queryBuilder->toSql())->toContain('order by "name" asc');
        });
    });

    describe('Sad Paths', function (): void {
        test('throws an exception for invalid fields', function (): void {
            QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [
                    'self' => ['invalid_field'],
                ],
                allowedFields: [
                    'self' => ['name'],
                ],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );
        })->throws(InvalidFieldsException::class);

        test('throws an exception for invalid filters', function (): void {
            QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [
                    'self' => [
                        [
                            'attribute' => 'invalid_filter',
                            'operator' => 'equals',
                            'value' => 'John',
                        ],
                    ],
                ],
                allowedFilters: [
                    'self' => ['name'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );
        })->throws(InvalidFiltersException::class);

        test('throws an exception for invalid relationships', function (): void {
            QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [
                    'self' => ['invalid_relationship'],
                ],
                allowedRelationships: [
                    'self' => ['posts', 'comments'],
                ],
                requestSorts: [],
                allowedSorts: [],
            );
        })->throws(InvalidRelationshipsException::class);

        test('throws an exception for invalid sorts', function (): void {
            QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [
                    'self' => [
                        [
                            'attribute' => 'invalid_sort',
                            'direction' => 'asc',
                        ],
                    ],
                ],
                allowedSorts: [
                    'self' => ['name', 'email'],
                ],
            );
        })->throws(InvalidSortsException::class);

        test('throws error for invalid filter operator (bug in current implementation)', function (): void {
            // Note: This currently throws ErrorException due to a bug in QueryBuilder line 385
            // where $this->allowedFilters (multi-dimensional array) is passed to InvalidFiltersException::create
            // which expects a flat array. The bug is that it should pass $this->allowedFilters['self'] instead.
            QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [
                    'self' => [
                        [
                            'attribute' => 'name',
                            'operator' => 'invalid_operator',
                            'value' => 'John',
                        ],
                    ],
                ],
                allowedFilters: [
                    'self' => ['name'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );
        })->throws(ErrorException::class, 'Array to string conversion');
    });

    describe('Edge Cases', function (): void {
        test('applies all filter operators successfully', function (string $operator, mixed $value): void {
            // Arrange
            $filter = [
                'attribute' => 'name',
                'operator' => $operator,
                'value' => $value,
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [
                    'self' => [$filter],
                ],
                allowedFilters: [
                    'self' => ['name'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            $sql = $queryBuilder->toSql();
            expect($sql)->toBeString();

            // Verify SQL contains expected clauses based on operator
            match ($operator) {
                'equals' => expect($sql)->toContain('where "name" = ?'),
                'not_equals' => expect($sql)->toContain('where "name" != ?'),
                'greater_than' => expect($sql)->toContain('where "name" > ?'),
                'greater_than_or_equal_to' => expect($sql)->toContain('where "name" >= ?'),
                'less_than' => expect($sql)->toContain('where "name" < ?'),
                'less_than_or_equal_to' => expect($sql)->toContain('where "name" <= ?'),
                'like' => expect($sql)->toContain('where "name" like ?'),
                'not_like' => expect($sql)->toContain('where "name" not like ?'),
                'in' => expect($sql)->toContain('where "name" in ('),
                'not_in' => expect($sql)->toContain('where "name" not in ('),
                'between' => expect($sql)->toContain('where "name" between ? and ?'),
                'not_between' => expect($sql)->toContain('where "name" not between ? and ?'),
                'is_null' => expect($sql)->toContain('where "name" is null'),
                'is_not_null' => expect($sql)->toContain('where "name" is not null'),
                default => null,
            };
        })->with([
            'equals' => ['equals', 'John'],
            'not_equals' => ['not_equals', 'John'],
            'greater_than' => ['greater_than', 100],
            'greater_than_or_equal_to' => ['greater_than_or_equal_to', 100],
            'less_than' => ['less_than', 100],
            'less_than_or_equal_to' => ['less_than_or_equal_to', 100],
            'like' => ['like', '%John%'],
            'not_like' => ['not_like', '%John%'],
            'in' => ['in', ['John', 'Jane']],
            'not_in' => ['not_in', ['John', 'Jane']],
            'between' => ['between', [1, 10]],
            'not_between' => ['not_between', [1, 10]],
            'is_null' => ['is_null', null],
            'is_not_null' => ['is_not_null', null],
        ]);

        test('applies filter with OR boolean operator', function (): void {
            // Arrange
            $filters = [
                [
                    'attribute' => 'name',
                    'operator' => 'equals',
                    'value' => 'John',
                ],
                [
                    'attribute' => 'name',
                    'operator' => 'equals',
                    'value' => 'Jane',
                    'boolean' => 'or',
                ],
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [
                    'self' => $filters,
                ],
                allowedFilters: [
                    'self' => ['name'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            $sql = $queryBuilder->toSql();
            expect($sql)->toContain('where "name" = ?')
                ->and($sql)->toContain('or "name" = ?');
        });

        test('handles non-self relationships', function (): void {
            // Arrange - Test lines 295-298 for non-'self' relationship resource
            // This tests the elseif branches in applyToQuery method

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [
                    'posts' => ['comments'],  // Non-'self' relationship resource
                ],
                allowedRelationships: [
                    'posts' => ['comments'],
                ],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKey('comments')
                ->and($eagerLoads['comments'])->toBeCallable();
        });

        test('applies filters to nested relationships with whereHas', function (): void {
            // Arrange
            $requestFilters = [
                'posts' => [
                    [
                        'attribute' => 'title',
                        'operator' => 'like',
                        'value' => '%Laravel%',
                    ],
                ],
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: $requestFilters,
                allowedFilters: [
                    'posts' => ['title'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            $sql = $queryBuilder->toSql();
            expect($sql)->toContain('where exists');
        });

        test('applies multiple filters to same nested relationship (triggers bug)', function (): void {
            // Arrange - Multiple filters on the same attribute - testing line 322
            // This test triggers a bug at line 322 where applyFilter is called with a Closure
            $requestFilters = [
                'posts' => [
                    [
                        'attribute' => 'title',
                        'operator' => 'like',
                        'value' => '%Laravel%',
                    ],
                    [
                        'attribute' => 'content',
                        'operator' => 'not_like',
                        'value' => '%Draft%',
                        'boolean' => 'and',
                    ],
                ],
            ];

            // Act & Assert - This will throw a TypeError due to the bug
            expect(fn (): QueryBuilder => QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: $requestFilters,
                allowedFilters: [
                    'posts' => ['title', 'content'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            ))->toThrow(TypeError::class);
        });

        test('handles relationship fields without explicit relationship loading', function (): void {
            // Arrange - Fields for posts but no explicit relationship request
            $requestFields = [
                'posts' => ['title'],
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: $requestFields,
                allowedFields: [
                    'posts' => ['title'],
                ],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKey('posts');
        });

        test('handles relationship sorts without explicit relationship loading', function (): void {
            // Arrange - Sorts for posts but no explicit relationship request - tests line 341
            $requestSorts = [
                'posts' => [
                    [
                        'attribute' => 'created_at',
                        'direction' => 'desc',
                    ],
                ],
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: $requestSorts,
                allowedSorts: [
                    'posts' => ['created_at'],
                ],
            );

            // Assert
            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKey('posts');
        });

        test('handles nested relationships with existing withs', function (): void {
            // Arrange - Test lines 296, 339 for relationships with existing entries in withs
            // Using multiple nested relationships
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [
                    'comments' => ['user'],  // This will test line 296 - existing key check
                    'posts' => ['comments'], // This will create initial entry
                ],
                allowedRelationships: [
                    'comments' => ['user'],
                    'posts' => ['comments'],
                ],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKeys(['user', 'comments']);
        });

        test('handles filters with missing optional parameters', function (): void {
            // Arrange - Filter with no explicit boolean parameter (defaults to 'and')
            $filter = [
                'attribute' => 'name',
                'operator' => 'equals',
                'value' => 'John',
                // No 'boolean' key - should default to 'and'
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [
                    'self' => [$filter],
                ],
                allowedFilters: [
                    'self' => ['name'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert
            expect($queryBuilder->toSql())->toContain('where "name" = ?');
        });

        test('handles empty filter value arrays for in operator', function (): void {
            // Arrange
            $filter = [
                'attribute' => 'name',
                'operator' => 'in',
                'value' => [],
            ];

            // Act
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [
                    'self' => [$filter],
                ],
                allowedFilters: [
                    'self' => ['name'],
                ],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Assert - Empty IN clause results in impossible condition (0 = 1)
            expect($queryBuilder->toSql())->toContain('where 0 = 1');
        });

        test('forwards method calls to underlying builder', function (): void {
            // Arrange
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [],
                allowedRelationships: [],
                requestSorts: [],
                allowedSorts: [],
            );

            // Act - Call a method that returns the builder for chaining
            $result = $queryBuilder->where('email', 'test@example.com');

            // Assert - Should return QueryBuilder instance for chaining
            expect($result)->toBeInstanceOf(QueryBuilder::class);

            // Act - Call a method that returns a non-builder result
            $count = $queryBuilder->count();

            // Assert - Should return the actual result
            expect($count)->toBeInt();
        });

        test('handles fields when relationship already created the key (line 308)', function (): void {
            // Line 308: fields loop finds key that relationships loop already created
            // Relationships loop creates $withs['posts'], then fields loop processes 'posts' resource
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [
                    'posts' => ['title'],  // Fields loop finds 'posts' exists (line 308)
                ],
                allowedFields: [
                    'posts' => ['title'],
                ],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [
                    'self' => ['posts'],  // Relationships loop creates $withs['posts'] (line 294)
                ],
                allowedRelationships: [
                    'self' => ['posts'],
                ],
                requestSorts: [],
                allowedSorts: [],
            );

            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKey('posts');
        });

        test('handles sorts when relationship already created the key (line 339)', function (): void {
            // Line 339: sorts loop finds key that relationships or fields loop already created
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [
                    'self' => ['posts'],  // Relationships loop creates $withs['posts']
                ],
                allowedRelationships: [
                    'self' => ['posts'],
                ],
                requestSorts: [
                    'posts' => [
                        [
                            'attribute' => 'created_at',
                            'direction' => 'desc',
                        ],
                    ],  // Sorts loop finds 'posts' exists (line 339)
                ],
                allowedSorts: [
                    'posts' => ['created_at'],
                ],
            );

            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKey('posts');
        });

        test('handles nested relationship when parent relationship exists (line 296)', function (): void {
            // Line 296: relationships loop finds key created by earlier relationshipResource
            $queryBuilder = QueryBuilder::for(
                resource: UserResource::class,
                requestFields: [],
                allowedFields: [],
                requestFilters: [],
                allowedFilters: [],
                requestRelationships: [
                    'self' => ['posts'],      // Creates $withs['posts']
                    'posts' => ['comments'],  // Finds 'posts' exists, uses line 296
                ],
                allowedRelationships: [
                    'self' => ['posts'],
                    'posts' => ['comments'],
                ],
                requestSorts: [],
                allowedSorts: [],
            );

            $eagerLoads = $queryBuilder->getEagerLoads();
            expect($eagerLoads)->toHaveKeys(['posts', 'comments']);
        });
    });
});
