<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fakes\Methods;

use Cline\RPC\Methods\AbstractListMethod;
use Override;
use Tests\Support\Resources\UserResource;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class ListUsers extends AbstractListMethod
{
    #[Override()]
    public function getName(): string
    {
        return 'users.list';
    }

    #[Override()]
    protected function getResourceClass(): string
    {
        return UserResource::class;
    }
}
