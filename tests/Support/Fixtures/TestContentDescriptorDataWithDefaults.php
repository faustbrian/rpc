<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fixtures;

use Cline\RPC\Data\AbstractContentDescriptorData;
use Override;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

/**
 * Test implementation with custom default content descriptors.
 *
 * This fixture demonstrates overriding the defaultContentDescriptors method
 * to provide custom default descriptor configurations for testing.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class TestContentDescriptorDataWithDefaults extends AbstractContentDescriptorData
{
    /**
     * Create a new test content descriptor data with defaults instance.
     *
     * @param string $title Required title field for testing
     */
    public function __construct(
        #[Required(), StringType()]
        public readonly string $title,
    ) {}

    /**
     * Override to provide custom default content descriptors.
     *
     * @return array<int, mixed> Array of default content descriptors
     */
    #[Override()]
    protected static function defaultContentDescriptors(): array
    {
        return [
            ['name' => 'custom', 'description' => 'Custom descriptor'],
            ['name' => 'another', 'description' => 'Another descriptor'],
        ];
    }
}
