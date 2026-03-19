<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fixtures;

use Cline\Struct\AbstractData as Data;
use Cline\Struct\Attributes\Validate;

/**
 * Test fixture for Data object parameter testing.
 * Simple product data structure with basic validation.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 * @psalm-immutable
 */
final readonly class ProductData extends Data
{
    public function __construct(
        #[Validate('required')]
        public string $title,
        #[Validate(['required', 'min:0'])]
        public float $price,
        public ?string $description = null,
    ) {}
}
