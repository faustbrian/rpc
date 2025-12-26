<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Contracts;

use Cline\OpenRpc\ValueObject\ContentDescriptorValue;
use Cline\RPC\Data\RequestObjectData;

/**
 * Contract for JSON-RPC method implementations.
 *
 * Defines the interface that all JSON-RPC method handlers must implement
 * to provide metadata for method discovery, parameter validation, and
 * result specification according to the OpenRPC specification.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface MethodInterface
{
    /**
     * Get the unique name identifier for this RPC method.
     *
     * The method name is used in JSON-RPC requests to route calls to the
     * appropriate handler. Should follow dot-notation convention for
     * namespaced methods (e.g., "user.create", "order.list").
     *
     * @return string Method name identifier
     */
    public function getName(): string;

    /**
     * Get a brief human-readable summary of the method's purpose.
     *
     * Used in API documentation and discovery endpoints to describe what
     * the method does. Should be concise (1-2 sentences) and focus on
     * the method's primary function.
     *
     * @return string Method summary description
     */
    public function getSummary(): string;

    /**
     * Get parameter definitions for this method.
     *
     * Returns an array of parameter descriptors that define the expected
     * input structure, types, and validation rules for method invocation.
     * Used for request validation and API documentation generation.
     *
     * @return array<int, mixed> Array of parameter content descriptors
     */
    public function getParams(): array;

    /**
     * Get the result content descriptor for this method.
     *
     * Defines the structure and type of the successful response payload.
     * Returns null for methods that don't return a value (notifications).
     *
     * @return null|ContentDescriptorValue Result descriptor or null for void methods
     */
    public function getResult(): ?ContentDescriptorValue;

    /**
     * Get error definitions that this method may produce.
     *
     * Returns an array of error objects describing the possible error
     * conditions, including error codes and descriptive messages.
     *
     * @return array<int, mixed> Array of error descriptors
     */
    public function getErrors(): array;

    /**
     * Inject the current request object into the method handler.
     *
     * Called by the dispatcher before method execution to provide access
     * to the full request context, including parameters, ID, and metadata.
     *
     * @param RequestObjectData $requestObject The incoming JSON-RPC request
     */
    public function setRequest(RequestObjectData $requestObject): void;
}
