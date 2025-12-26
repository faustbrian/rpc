<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fakes\Methods;

use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Methods\AbstractMethod;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class Subtract extends AbstractMethod
{
    public function handle(RequestObjectData $requestObject): int
    {
        return $requestObject->getParam('data.0') - $requestObject->getParam('data.1');
    }
}
