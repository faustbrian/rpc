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
 * Test fixture for Data object parameter validation in CallMethod tests.
 * Contains validation rules to test validateAndCreate behavior.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 * @psalm-immutable
 */
final readonly class ValidatedUserData extends Data
{
    public function __construct(
        #[Validate(['required', 'max:100', 'min:3'])]
        public string $name,
        #[Validate(['required', 'email'])]
        public string $email,
        #[Validate(['max:150', 'min:1'])]
        public ?int $age = null,
    ) {}
}
