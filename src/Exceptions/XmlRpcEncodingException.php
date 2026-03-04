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
 * Base exception for XML-RPC encoding failures.
 *
 * This exception is thrown when the XML-RPC protocol implementation encounters
 * an error while encoding a request or response to XML format. This typically
 * occurs due to invalid data structures or XML generation failures.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class XmlRpcEncodingException extends RuntimeException implements RpcException
{
    public static function request(?Throwable $previous = null): XmlRpcRequestEncodingException
    {
        return new XmlRpcRequestEncodingException(previous: $previous);
    }

    public static function response(?Throwable $previous = null): XmlRpcResponseEncodingException
    {
        return new XmlRpcResponseEncodingException(previous: $previous);
    }
}
