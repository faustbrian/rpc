<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fakes;

use Cline\RPC\Servers\AbstractServer;
use Override;
use RuntimeException;

/**
 * Server that throws unexpected exception during method repository access.
 * Used to test outer catch block in RequestHandler (lines 176-180).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnexpectedExceptionServer extends AbstractServer
{
    #[Override()]
    public function methods(): array
    {
        throw new RuntimeException('Unexpected error');
    }
}
