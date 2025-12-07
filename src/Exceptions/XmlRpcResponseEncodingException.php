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
 * Exception thrown when XML-RPC response encoding fails.
 *
 * Thrown when the XML-RPC protocol encounters an error while encoding
 * a response to XML format.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class XmlRpcResponseEncodingException extends XmlRpcEncodingException
{
    public function __construct(string $message = 'XML-RPC response encoding failed', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for response encoding failure.
     *
     * @param  Throwable $previous The underlying exception that caused the encoding failure
     * @return self      The created exception instance
     */
    public static function fromPrevious(Throwable $previous): self
    {
        return new self(previous: $previous);
    }
}
