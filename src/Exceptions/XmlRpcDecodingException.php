<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for XML-RPC decoding failures.
 *
 * This exception is thrown when the XML-RPC protocol implementation encounters
 * an error while decoding XML to internal data structures. This typically
 * occurs due to malformed XML or unexpected XML structure.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class XmlRpcDecodingException extends RuntimeException implements RpcException
{
    public static function request(?Throwable $previous = null): XmlRpcRequestDecodingException
    {
        return new XmlRpcRequestDecodingException(previous: $previous);
    }

    public static function response(?Throwable $previous = null): XmlRpcResponseDecodingException
    {
        return new XmlRpcResponseDecodingException(previous: $previous);
    }
}
