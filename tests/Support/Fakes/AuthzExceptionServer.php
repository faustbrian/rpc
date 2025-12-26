<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fakes;

use Cline\RPC\Repositories\MethodRepository;
use Cline\RPC\Servers\AbstractServer;
use Illuminate\Auth\Access\AuthorizationException;
use Override;

/**
 * Test server that throws AuthorizationException during method repository access.
 * Used to test authorization error handling at the server level.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AuthzExceptionServer extends AbstractServer
{
    #[Override()]
    public function methods(): array
    {
        return [];
    }

    #[Override()]
    public function getMethodRepository(): MethodRepository
    {
        throw new AuthorizationException('Server requires authorization');
    }
}
