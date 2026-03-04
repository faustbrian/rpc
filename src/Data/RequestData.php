<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

/**
 * Represents a JSON-RPC 2.0 request container.
 *
 * Encapsulates one or more JSON-RPC request objects along with metadata
 * indicating whether the request is a single call or a batch operation.
 * Supports both individual method invocations and batched requests for
 * improved network efficiency and transaction-like behavior.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.jsonrpc.org/specification#batch
 */
final class RequestData extends AbstractData
{
    /**
     * Create a new request container instance.
     *
     * @param array<int, array{jsonrpc: string, method: string, id?: null|int|string, params?: null|array<string, mixed>}> $requestObjects Collection of raw JSON-RPC
     *                                                                                                                                     request arrays to be processed.
     *                                                                                                                                     Contains a single element for individual
     *                                                                                                                                     requests or multiple elements for batch
     *                                                                                                                                     operations that should be executed together.
     * @param bool                                                                                                         $isBatch        Indicates whether this is a batch request containing
     *                                                                                                                                     multiple operations. Batch requests allow clients to send
     *                                                                                                                                     multiple method calls in a single HTTP request, reducing
     *                                                                                                                                     network overhead and enabling transactional semantics.
     */
    public function __construct(
        public readonly array $requestObjects,
        public readonly bool $isBatch,
    ) {}
}
