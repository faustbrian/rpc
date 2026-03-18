<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fixtures;

use Cline\Struct\Attributes\Validate;
use Cline\Struct\AbstractData as Data;

/**
 * Test fixture for Data object parameter validation in CallMethod tests.
 * Contains validation rules to test validateAndCreate behavior.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final readonly class ValidatedUserData extends Data
{
    public function __construct(
        #[Validate(['required', 'max:100', 'min:3'])]
        public readonly string $name,
        #[Validate(['required', 'email'])]
        public readonly string $email,
        #[Validate(['max:150', 'min:1'])]
        public readonly ?int $age = null,
    ) {}
}
