<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fakes\Methods;

use Cline\RPC\Methods\AbstractMethod;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class GetData extends AbstractMethod
{
    public function handle(): array
    {
        return ['hello', 5];
    }
}
