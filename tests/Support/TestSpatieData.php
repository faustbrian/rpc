<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support;

use Cline\Struct\AbstractData as Data;

/**
 * Test implementation of Spatie Data for unit testing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class TestSpatieData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}
