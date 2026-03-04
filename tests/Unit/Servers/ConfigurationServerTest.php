<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Data\Configuration\ServerData;
use Cline\RPC\Servers\ConfigurationServer;
use Illuminate\Support\Facades\Config;
use Tests\Support\Fakes\Methods\GetData;
use Tests\Support\Fakes\Methods\NotifyHello;
use Tests\Support\Fakes\Methods\Subtract;
use Tests\Support\Fakes\Methods\Sum;

describe('ConfigurationServer', function (): void {
    test('creates server from configuration and accesses properties', function (): void {
        $serverData = ServerData::from([
            'name' => 'test',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => null,
            'content_descriptors' => [],
            'schemas' => [],
        ]);

        $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));

        expect($server)->toBeInstanceOf(ConfigurationServer::class);
        expect($server->getName())->toBe('test');
        expect($server->getRoutePath())->toBe('/rpc');
        expect($server->getRouteName())->toBe('rpc');
        expect($server->getVersion())->toBe('1.0');
        expect($server->getMiddleware())->toBe([]);
    });

    test('returns configured methods when explicitly defined', function (): void {
        $configuredMethods = [
            GetData::class,
            Sum::class,
            NotifyHello::class,
        ];

        $serverData = ServerData::from([
            'name' => 'test',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => $configuredMethods,
            'content_descriptors' => [],
            'schemas' => [],
        ]);

        $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));

        expect($server->methods())->toBe($configuredMethods);
    });

    test('returns empty array when methods directory does not exist', function (): void {
        Config::set('rpc.paths.methods', '/non/existent/directory');
        Config::set('rpc.namespaces.methods', 'App\\Methods');

        $serverData = ServerData::from([
            'name' => 'test',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => null,
            'content_descriptors' => [],
            'schemas' => [],
        ]);

        $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));

        expect($server->methods())->toBe([]);
    });

    test('auto-discovers method files from configured directory', function (): void {
        // Use absolute path for test support methods
        $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
        $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

        Config::set('rpc.paths.methods', $methodsPath);
        Config::set('rpc.namespaces.methods', $methodsNamespace);

        $serverData = ServerData::from([
            'name' => 'test-auto-discover',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => null,
            'content_descriptors' => [],
            'schemas' => [],
        ]);

        $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
        $methods = $server->methods();

        expect($methods)->toBeArray();
        expect(count($methods))->toBeGreaterThan(0);
        // These are actual test methods that implement MethodInterface
        expect($methods)->toContain(GetData::class);
        expect($methods)->toContain(Sum::class);
        expect($methods)->toContain(Subtract::class);
    });

    test('returns content descriptors and schemas from configuration', function (): void {
        $contentDescriptors = [
            (object) ['name' => 'descriptor1'],
            (object) ['name' => 'descriptor2'],
        ];

        $schemas = [
            (object) ['type' => 'schema1'],
            (object) ['type' => 'schema2'],
        ];

        $serverData = ServerData::from([
            'name' => 'test',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => null,
            'content_descriptors' => $contentDescriptors,
            'schemas' => $schemas,
        ]);

        $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));

        expect($server->getContentDescriptors())->toBe($contentDescriptors);
        expect($server->getSchemas())->toBe($schemas);
    });

    test('methods() can be called multiple times', function (): void {
        $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
        $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

        Config::set('rpc.paths.methods', $methodsPath);
        Config::set('rpc.namespaces.methods', $methodsNamespace);

        $serverData = ServerData::from([
            'name' => 'test',
            'path' => '/rpc',
            'route' => 'rpc',
            'version' => '1.0',
            'middleware' => [],
            'methods' => null,
            'content_descriptors' => [],
            'schemas' => [],
        ]);

        $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));

        // Call methods() multiple times - should work without errors
        $methods1 = $server->methods();
        $methods2 = $server->methods();
        $methods3 = $server->methods();

        expect($methods1)->toBeArray();
        expect($methods2)->toBeArray();
        expect($methods3)->toBeArray();
        expect($methods1)->toBe($methods2);
        expect($methods2)->toBe($methods3);
    });

    describe('Auto-discovery Pipeline Coverage', function (): void {
        test('executes full transformation pipeline with real method files', function (): void {
            // Arrange - Use actual test methods directory
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-pipeline',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - This will hit lines 131-142 and 145
            expect($methods)->toBeArray();
            expect($methods)->toContain(GetData::class);
            expect($methods)->toContain(Sum::class);
            expect($methods)->toContain(Subtract::class);
            expect($methods)->toContain(NotifyHello::class);

            // Verify methods have required interface
            foreach ($methods as $method) {
                expect(class_implements($method))->toContain(MethodInterface::class);
            }
        });

        test('discovers methods from nested directory structures', function (): void {
            // Arrange
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-nested',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Verifies path to namespace conversion (lines 136-139)
            expect($methods)->toBeArray();
            expect(count($methods))->toBeGreaterThan(0);

            // Each method class should be properly namespaced
            foreach ($methods as $method) {
                expect($method)->toStartWith($methodsNamespace);
                expect($method)->not->toContain('/');
                expect($method)->toContain('\\');
            }
        });

        test('applies ucfirst correctly during namespace transformation', function (): void {
            // Arrange
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-ucfirst',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Line 139 applies ucfirst
            expect($methods)->toBeArray();

            foreach ($methods as $method) {
                $className = class_basename($method);
                // First character should be uppercase
                expect($className[0])->toBe(mb_strtoupper($className[0]));
            }
        });

        test('filters out Abstract and Test files during real discovery', function (): void {
            // Arrange
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-filtering',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Line 136 rejects AbstractMethod and Test.php filenames
            expect($methods)->toBeArray();

            foreach ($methods as $method) {
                expect($method)->not->toContain('AbstractMethod');
                // Class names shouldn't end with "Test" (test files), but namespace can contain "Tests"
                expect(class_basename($method))->not->toEndWith('Test');
            }
        });

        test('validates methods implement MethodInterface during discovery', function (): void {
            // Arrange
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-interface-check',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Line 141 filters by interface implementation
            expect($methods)->toBeArray();
            expect(count($methods))->toBeGreaterThan(0);

            foreach ($methods as $method) {
                $implements = class_implements($method);
                expect($implements)->toBeArray();
                expect(in_array(MethodInterface::class, $implements, true))->toBeTrue();
            }
        });

        test('returns discovered methods array correctly', function (): void {
            // Arrange
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/Methods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\Methods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-return',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Line 145 returns the methods array
            expect($methods)->toBeArray();
            expect($methods)->not->toBeEmpty();

            // Verify structure
            $keys = array_keys($methods);
            expect($keys)->toEqual(range(0, count($methods) - 1));
        });
    });

    describe('OpenRPC Configuration', function (): void {
        test('returns content descriptors from server configuration', function (): void {
            // Arrange
            $contentDescriptors = [
                (object) [
                    'name' => 'User',
                    'schema' => (object) ['type' => 'object'],
                ],
                (object) [
                    'name' => 'Post',
                    'schema' => (object) ['type' => 'object'],
                ],
            ];

            $serverData = ServerData::from([
                'name' => 'test',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => $contentDescriptors,
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $result = $server->getContentDescriptors();

            // Assert - Line 160
            expect($result)->toBeArray();
            expect($result)->toBe($contentDescriptors);
            expect($result)->toHaveCount(2);
            expect($result[0]->name)->toBe('User');
            expect($result[1]->name)->toBe('Post');
        });

        test('returns schemas from server configuration', function (): void {
            // Arrange
            $schemas = [
                (object) [
                    'title' => 'UserSchema',
                    'type' => 'object',
                    'properties' => (object) [
                        'id' => (object) ['type' => 'integer'],
                        'name' => (object) ['type' => 'string'],
                    ],
                ],
                (object) [
                    'title' => 'PostSchema',
                    'type' => 'object',
                    'properties' => (object) [
                        'id' => (object) ['type' => 'integer'],
                        'title' => (object) ['type' => 'string'],
                    ],
                ],
            ];

            $serverData = ServerData::from([
                'name' => 'test',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => $schemas,
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $result = $server->getSchemas();

            // Assert - Line 174
            expect($result)->toBeArray();
            expect($result)->toBe($schemas);
            expect($result)->toHaveCount(2);
            expect($result[0]->title)->toBe('UserSchema');
            expect($result[1]->title)->toBe('PostSchema');
        });

        test('handles empty content descriptors array', function (): void {
            // Arrange
            $serverData = ServerData::from([
                'name' => 'test',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $result = $server->getContentDescriptors();

            // Assert
            expect($result)->toBeArray();
            expect($result)->toBeEmpty();
        });

        test('handles empty schemas array', function (): void {
            // Arrange
            $serverData = ServerData::from([
                'name' => 'test',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $result = $server->getSchemas();

            // Assert
            expect($result)->toBeArray();
            expect($result)->toBeEmpty();
        });

        test('preserves complex nested structures in content descriptors', function (): void {
            // Arrange
            $contentDescriptors = [
                (object) [
                    'name' => 'ComplexUser',
                    'schema' => (object) [
                        'type' => 'object',
                        'properties' => (object) [
                            'profile' => (object) [
                                'type' => 'object',
                                'properties' => (object) [
                                    'avatar' => (object) ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $serverData = ServerData::from([
                'name' => 'test',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => $contentDescriptors,
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $result = $server->getContentDescriptors();

            // Assert
            expect($result)->toBeArray();
            expect($result[0]->schema->properties->profile->properties->avatar->type)->toBe('string');
        });

        test('preserves complex nested structures in schemas', function (): void {
            // Arrange
            $schemas = [
                (object) [
                    'title' => 'NestedSchema',
                    'type' => 'object',
                    'properties' => (object) [
                        'metadata' => (object) [
                            'type' => 'object',
                            'properties' => (object) [
                                'tags' => (object) [
                                    'type' => 'array',
                                    'items' => (object) ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $serverData = ServerData::from([
                'name' => 'test',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => $schemas,
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $result = $server->getSchemas();

            // Assert
            expect($result)->toBeArray();
            expect($result[0]->properties->metadata->properties->tags->items->type)->toBe('string');
        });
    });

    describe('Edge Cases and Error Handling', function (): void {
        test('filters out PHP files that do not contain valid classes', function (): void {
            // Arrange - Use directory with invalid PHP file
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/InvalidMethods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\InvalidMethods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-invalid',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Line 140-144: class_exists check and interface validation should filter out invalid files
            expect($methods)->toBeArray();
            expect($methods)->toBeEmpty(); // No valid method classes in InvalidMethods directory
        });

        test('filters out classes that do not implement MethodInterface', function (): void {
            // Arrange - Directory contains NoInterface.php (valid class but no MethodInterface)
            $methodsPath = realpath(__DIR__.'/../../Support/Fakes/InvalidMethods');
            $methodsNamespace = 'Tests\\Support\\Fakes\\InvalidMethods';

            Config::set('rpc.paths.methods', $methodsPath);
            Config::set('rpc.namespaces.methods', $methodsNamespace);

            $serverData = ServerData::from([
                'name' => 'test-no-interface',
                'path' => '/rpc',
                'route' => 'rpc',
                'version' => '1.0',
                'middleware' => [],
                'methods' => null,
                'content_descriptors' => [],
                'schemas' => [],
            ]);

            // Act
            $server = new ConfigurationServer($serverData, Config::get('rpc.paths.methods', ''), Config::get('rpc.namespaces.methods', ''));
            $methods = $server->methods();

            // Assert - Line 144: in_array check should filter out classes without MethodInterface
            expect($methods)->toBeArray();
            expect($methods)->toBeEmpty();
        });
    });
});
