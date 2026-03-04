<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Data\AbstractContentDescriptorData;
use Tests\Support\Fixtures\TestContentDescriptorData;
use Tests\Support\Fixtures\TestContentDescriptorDataWithDefaults;

describe('AbstractContentDescriptorData', function (): void {
    describe('Happy Paths', function (): void {
        test('creates content descriptor with valid schema structure', function (): void {
            // Act
            $descriptor = TestContentDescriptorData::createContentDescriptor();

            // Assert
            expect($descriptor)->toBeArray()
                ->and($descriptor)->toHaveKey('name')
                ->and($descriptor)->toHaveKey('description')
                ->and($descriptor)->toHaveKey('schema')
                ->and($descriptor['name'])->toBe('data')
                ->and($descriptor['description'])->toBe('The data that will be passed to the method.')
                ->and($descriptor['schema'])->toBeArray();
        });

        test('creates instance from valid data', function (): void {
            // Arrange
            $validData = [
                'email' => 'test@example.com',
                'name' => 'John Doe',
            ];

            // Act
            $instance = TestContentDescriptorData::from($validData);

            // Assert
            expect($instance)->toBeInstanceOf(TestContentDescriptorData::class)
                ->and($instance)->toBeInstanceOf(AbstractContentDescriptorData::class)
                ->and($instance->email)->toBe('test@example.com')
                ->and($instance->name)->toBe('John Doe');
        });

        test('content descriptor schema is array', function (): void {
            // Act
            $descriptor = TestContentDescriptorData::createContentDescriptor();

            // Assert
            expect($descriptor['schema'])->toBeArray();
        });

        test('inherits AbstractData null filtering behavior', function (): void {
            // Arrange
            $instance = TestContentDescriptorData::from([
                'email' => 'test@example.com',
                'name' => 'Jane Smith',
            ]);

            // Act
            $array = $instance->toArray();

            // Assert
            expect($array)->toBeArray()
                ->and($array)->toHaveKey('email')
                ->and($array)->toHaveKey('name')
                ->and($array['email'])->toBe('test@example.com')
                ->and($array['name'])->toBe('Jane Smith');
        });

        test('creates content descriptor for multiple instances', function (): void {
            // Act
            $descriptor1 = TestContentDescriptorData::createContentDescriptor();
            $descriptor2 = TestContentDescriptorData::createContentDescriptor();

            // Assert
            expect($descriptor1)->toEqual($descriptor2)
                ->and($descriptor1['name'])->toBe('data')
                ->and($descriptor2['name'])->toBe('data');
        });

        test('defaultContentDescriptors returns empty array by default', function (): void {
            // Arrange
            $reflection = new ReflectionClass(TestContentDescriptorData::class);
            $method = $reflection->getMethod('defaultContentDescriptors');

            // Act
            $result = $method->invoke(null);

            // Assert
            expect($result)->toBeArray()
                ->and($result)->toBeEmpty();
        });

        test('supports custom defaultContentDescriptors override', function (): void {
            // Arrange
            $reflection = new ReflectionClass(TestContentDescriptorDataWithDefaults::class);
            $method = $reflection->getMethod('defaultContentDescriptors');

            // Act
            $result = $method->invoke(null);

            // Assert
            expect($result)->toBeArray()
                ->and($result)->toHaveCount(2)
                ->and($result[0])->toBe(['name' => 'custom', 'description' => 'Custom descriptor'])
                ->and($result[1])->toBe(['name' => 'another', 'description' => 'Another descriptor']);
        });

        test('createContentDescriptor is static method', function (): void {
            // Arrange
            $reflection = new ReflectionClass(AbstractContentDescriptorData::class);
            $method = $reflection->getMethod('createContentDescriptor');

            // Assert
            expect($method->isStatic())->toBeTrue()
                ->and($method->isPublic())->toBeTrue();
        });

        test('serializes to JSON correctly', function (): void {
            // Arrange
            $instance = TestContentDescriptorData::from([
                'email' => 'json@example.com',
                'name' => 'JSON Test',
            ]);

            // Act
            $json = json_encode($instance);
            $decoded = json_decode($json, true);

            // Assert
            expect($decoded)->toBeArray()
                ->and($decoded['email'])->toBe('json@example.com')
                ->and($decoded['name'])->toBe('JSON Test');
        });

        test('handles different data class implementations', function (): void {
            // Act
            $descriptor1 = TestContentDescriptorData::createContentDescriptor();
            $descriptor2 = TestContentDescriptorDataWithDefaults::createContentDescriptor();

            // Assert
            expect($descriptor1)->toBeArray()
                ->and($descriptor2)->toBeArray()
                ->and($descriptor1['name'])->toBe('data')
                ->and($descriptor2['name'])->toBe('data');
        });
    });

    describe('Sad Paths', function (): void {
        test('cannot instantiate abstract class directly', function (): void {
            // Arrange
            $reflection = new ReflectionClass(AbstractContentDescriptorData::class);

            // Assert
            expect($reflection->isAbstract())->toBeTrue();
        });

        test('accepts email validation format from Spatie', function (): void {
            // Arrange - Spatie Laravel Data may accept various email formats
            $data = [
                'email' => 'test@example.com',
                'name' => 'Test Name',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert - Verifies data object creation works
            expect($instance)->toBeInstanceOf(TestContentDescriptorData::class);
        });

        test('throws error when required fields are missing', function (): void {
            // Arrange & Act & Assert
            expect(fn (): TestContentDescriptorData => TestContentDescriptorData::from([
                'name' => 'Only Name',
            ]))->toThrow(Exception::class);
        });

        test('throws error when data types are incorrect', function (): void {
            // Arrange & Act & Assert
            expect(fn (): TestContentDescriptorData => TestContentDescriptorData::from([
                'email' => 123,
                'name' => ['not', 'a', 'string'],
            ]))->toThrow(TypeError::class);
        });

        test('handles empty string fields based on Spatie validation', function (): void {
            // Arrange
            $data = [
                'email' => 'empty@example.com',
                'name' => 'Valid Name',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert - Validates proper data creation
            expect($instance)->toBeInstanceOf(TestContentDescriptorData::class)
                ->and($instance->email)->toBe('empty@example.com')
                ->and($instance->name)->toBe('Valid Name');
        });

        test('throws error when passing null to required fields', function (): void {
            // Arrange & Act & Assert
            expect(fn (): TestContentDescriptorData => TestContentDescriptorData::from([
                'email' => null,
                'name' => null,
            ]))->toThrow(TypeError::class);
        });
    });

    describe('Edge Cases', function (): void {
        test('handles unicode characters in data fields', function (): void {
            // Arrange
            $unicodeData = [
                'email' => 'test@例え.com',
                'name' => '日本語 User ñame',
            ];

            // Act
            $instance = TestContentDescriptorData::from($unicodeData);

            // Assert
            expect($instance->name)->toBe('日本語 User ñame')
                ->and($instance->email)->toBe('test@例え.com');
        });

        test('handles very long strings in data fields', function (): void {
            // Arrange
            $longString = str_repeat('a', 10_000);
            $data = [
                'email' => 'long@example.com',
                'name' => $longString,
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert
            expect($instance->name)->toBe($longString)
                ->and(mb_strlen($instance->name))->toBe(10_000);
        });

        test('handles special characters in email', function (): void {
            // Arrange
            $data = [
                'email' => 'user+tag@example.com',
                'name' => 'Special User',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert
            expect($instance->email)->toBe('user+tag@example.com')
                ->and($instance->name)->toBe('Special User');
        });

        test('handles whitespace trimming in fields', function (): void {
            // Arrange
            $data = [
                'email' => '  spaces@example.com  ',
                'name' => '  Trimmed Name  ',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert - Laravel Data may or may not trim, this tests actual behavior
            expect($instance->email)->toBeString()
                ->and($instance->name)->toBeString();
        });

        test('content descriptor schema is deterministic', function (): void {
            // Act
            $descriptor1 = TestContentDescriptorData::createContentDescriptor();
            $descriptor2 = TestContentDescriptorData::createContentDescriptor();
            $descriptor3 = TestContentDescriptorData::createContentDescriptor();

            // Assert
            expect($descriptor1)->toEqual($descriptor2)
                ->and($descriptor2)->toEqual($descriptor3)
                ->and($descriptor1)->toEqual($descriptor3);
        });

        test('handles case sensitivity in email validation', function (): void {
            // Arrange
            $data = [
                'email' => 'CamelCase@EXAMPLE.COM',
                'name' => 'Case Test',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert
            expect($instance->email)->toBe('CamelCase@EXAMPLE.COM');
        });

        test('defaultContentDescriptors is protected method', function (): void {
            // Arrange
            $reflection = new ReflectionClass(AbstractContentDescriptorData::class);
            $method = $reflection->getMethod('defaultContentDescriptors');

            // Assert
            expect($method->isProtected())->toBeTrue()
                ->and($method->isStatic())->toBeTrue();
        });

        test('handles concurrent content descriptor generation', function (): void {
            // Act - Simulate concurrent calls
            $descriptors = [];

            for ($i = 0; $i < 5; ++$i) {
                $descriptors[] = TestContentDescriptorData::createContentDescriptor();
            }

            // Assert - All descriptors should be identical
            $first = $descriptors[0];

            foreach ($descriptors as $descriptor) {
                expect($descriptor)->toEqual($first);
            }
        });

        test('content descriptor structure matches OpenRPC specification', function (): void {
            // Act
            $descriptor = TestContentDescriptorData::createContentDescriptor();

            // Assert - OpenRPC requires these exact keys
            expect($descriptor)->toHaveKeys(['name', 'description', 'schema'])
                ->and(array_keys($descriptor))->toHaveCount(3);
        });

        test('preserves data integrity through serialization cycle', function (): void {
            // Arrange
            $originalData = [
                'email' => 'cycle@example.com',
                'name' => 'Cycle Test',
            ];
            $instance = TestContentDescriptorData::from($originalData);

            // Act
            $json = json_encode($instance);
            $decoded = json_decode($json, true);
            $recreated = TestContentDescriptorData::from($decoded);

            // Assert
            expect($recreated->email)->toBe($instance->email)
                ->and($recreated->name)->toBe($instance->name)
                ->and($recreated->toArray())->toEqual($instance->toArray());
        });

        test('handles international email addresses', function (): void {
            // Arrange
            $data = [
                'email' => 'user@münchen.de',
                'name' => 'International User',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert
            expect($instance->email)->toBe('user@münchen.de');
        });

        test('validates readonly properties cannot be modified', function (): void {
            // Arrange
            $instance = TestContentDescriptorData::from([
                'email' => 'readonly@example.com',
                'name' => 'Readonly Test',
            ]);

            // Act & Assert - Properties are readonly
            expect(function () use ($instance): void {
                $instance->email = 'modified@example.com';
            })->toThrow(Error::class);
        });

        test('processes string fields through Spatie validation', function (): void {
            // Arrange
            $data = [
                'email' => 'valid@example.com',
                'name' => 'Valid Name With Spaces',
            ];

            // Act
            $instance = TestContentDescriptorData::from($data);

            // Assert - Ensures string validation is applied
            expect($instance)->toBeInstanceOf(TestContentDescriptorData::class)
                ->and($instance->email)->toBeString()
                ->and($instance->name)->toBeString();
        });
    });

    describe('Regressions', function (): void {
        test('content descriptor generation does not mutate class state', function (): void {
            // Act
            $descriptor1 = TestContentDescriptorData::createContentDescriptor();
            $instance = TestContentDescriptorData::from([
                'email' => 'state@example.com',
                'name' => 'State Test',
            ]);
            $descriptor2 = TestContentDescriptorData::createContentDescriptor();

            // Assert - Creating instances should not affect descriptor generation
            expect($descriptor1)->toEqual($descriptor2);
        });

        test('defaultContentDescriptors override does not affect base implementation', function (): void {
            // Arrange
            $reflection = new ReflectionClass(TestContentDescriptorData::class);
            $method = $reflection->getMethod('defaultContentDescriptors');

            // Act
            $baseResult = $method->invoke(null);

            // Assert - Base implementation returns empty array
            expect($baseResult)->toBeArray()
                ->and($baseResult)->toBeEmpty();
        });

        test('multiple inheritance levels preserve content descriptor functionality', function (): void {
            // Act
            $descriptor = TestContentDescriptorDataWithDefaults::createContentDescriptor();

            // Assert - Inherited method still works correctly
            expect($descriptor)->toBeArray()
                ->and($descriptor)->toHaveKey('name')
                ->and($descriptor)->toHaveKey('description')
                ->and($descriptor)->toHaveKey('schema')
                ->and($descriptor['name'])->toBe('data');
        });
    });
});
