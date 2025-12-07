<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data\Configuration;

use Cline\RPC\Data\AbstractData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\DataCollection;

/**
 * Main configuration data for the JSON-RPC package.
 *
 * Holds the complete configuration structure including namespace mappings,
 * file paths, resource definitions, and server configurations. This data
 * object is populated from the rpc.php configuration file and used
 * throughout the application lifecycle.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ConfigurationData extends AbstractData
{
    /**
     * Create a new configuration data instance.
     *
     * @param array<string, string>           $namespaces Namespace configuration mappings that define
     *                                                    where RPC components are located. Maps namespace
     *                                                    prefixes to base namespaces for automatic class
     *                                                    discovery during server initialization (e.g.,
     *                                                    'methods' => 'App\\Rpc\\Methods').
     * @param array<string, string>           $paths      File system path mappings defining directory
     *                                                    locations for RPC components. Used for scanning
     *                                                    and discovering classes during server bootstrap
     *                                                    and method registration (e.g., 'methods' =>
     *                                                    app_path('Rpc/Methods')).
     * @param array<string, mixed>            $resources  Resource transformation configuration defining
     *                                                    how data models are converted to standardized
     *                                                    JSON representations. Currently unused but
     *                                                    reserved for future resource mapping features.
     * @param DataCollection<int, ServerData> $servers    Collection of server configuration objects
     *                                                    defining available RPC endpoints, their
     *                                                    routes, middleware stacks, and capabilities.
     *                                                    Each server represents a separate endpoint
     *                                                    with its own method set and configuration.
     */
    public function __construct(
        public readonly array $namespaces,
        public readonly array $paths,
        #[Present()]
        public readonly array $resources,
        #[DataCollectionOf(ServerData::class)]
        public readonly DataCollection $servers,
    ) {}
}
