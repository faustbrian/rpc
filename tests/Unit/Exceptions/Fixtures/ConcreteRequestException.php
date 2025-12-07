<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Exceptions\Fixtures;

use Cline\RPC\Exceptions\AbstractRequestException;

/**
 * Concrete test implementation of AbstractRequestException.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
final class ConcreteRequestException extends AbstractRequestException
{
    public static function make(int $code, string $message, ?array $data = null): self
    {
        return self::new($code, $message, $data);
    }
}
