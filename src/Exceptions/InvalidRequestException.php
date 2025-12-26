<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

/**
 * Base exception for JSON-RPC request that is malformed or structurally invalid.
 *
 * Represents JSON-RPC error code -32600 for requests that fail basic structural
 * validation, such as missing required members (jsonrpc, method, id), invalid
 * JSON-RPC version, or malformed request structure. This is distinct from parameter
 * validation errors which use error code -32602.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class InvalidRequestException extends AbstractRequestException
{
    // Abstract base - no factory methods
}
