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
use Tests\Support\Fakes\Methods\GetCollectionData;
use Tests\Support\Fakes\Methods\GetData;
use Tests\Support\Fakes\Methods\GetSpatieData;
use Tests\Support\Fakes\Methods\NotifyHello;
use Tests\Support\Fakes\Methods\NotifySum;
use Tests\Support\Fakes\Methods\RequiresAuthentication;
use Tests\Support\Fakes\Methods\RequiresAuthorization;
use Tests\Support\Fakes\Methods\Subtract;
use Tests\Support\Fakes\Methods\SubtractWithBinding;
use Tests\Support\Fakes\Methods\Sum;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class Server extends AbstractServer
{
    #[Override()]
    public function methods(): array
    {
        return [
            GetCollectionData::class,
            GetData::class,
            GetSpatieData::class,
            NotifyHello::class,
            NotifySum::class,
            RequiresAuthentication::class,
            RequiresAuthorization::class,
            Subtract::class,
            SubtractWithBinding::class,
            Sum::class,
        ];
    }
}
