<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fixtures;

use Cline\RPC\Data\AbstractContentDescriptorData;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

/**
 * Concrete test implementation of AbstractContentDescriptorData.
 *
 * This fixture provides a concrete implementation for testing the abstract
 * AbstractContentDescriptorData class functionality, including content
 * descriptor generation from data class definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class TestContentDescriptorData extends AbstractContentDescriptorData
{
    /**
     * Create a new test content descriptor data instance.
     *
     * @param string $email Required email field for testing validation
     * @param string $name  Required name field for testing validation
     */
    public function __construct(
        #[Required(), Email()]
        public readonly string $email,
        #[Required(), StringType()]
        public readonly string $name,
    ) {}
}
