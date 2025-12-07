<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Exception thrown when the JSON-RPC request cannot be parsed.
 *
 * This exception represents a JSON-RPC parse error (-32700), which occurs when
 * the server receives invalid JSON that cannot be parsed. This is typically
 * caused by malformed JSON syntax in the request body.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ParseErrorException extends AbstractRequestException
{
    /**
     * Create a new parse error exception instance.
     *
     * @param  null|array<int|string, mixed> $data Additional error data to include in the JSON-RPC
     *                                             error response. This array is formatted according
     *                                             to JSON:API error object specifications and can
     *                                             contain detailed information about the parse failure.
     * @return self                          The created exception instance with JSON-RPC error code -32700
     */
    public static function create(?array $data = null): self
    {
        return self::new(-32_700, 'Parse error', $data);
    }
}
