<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Throwable;

/**
 * Exception thrown when XML-RPC request decoding fails.
 *
 * Thrown when the XML-RPC protocol encounters an error while decoding
 * an XML request to internal data structures.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class XmlRpcRequestDecodingException extends XmlRpcDecodingException
{
    public function __construct(string $message = 'XML-RPC request decoding failed', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for request decoding failure.
     *
     * @param  Throwable $previous The underlying exception that caused the decoding failure
     * @return self      The created exception instance
     */
    public static function fromPrevious(Throwable $previous): self
    {
        return new self(previous: $previous);
    }
}
