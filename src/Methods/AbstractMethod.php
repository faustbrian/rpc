<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Methods;

use Cline\OpenRpc\ValueObject\ContentDescriptorValue;
use Cline\OpenRpc\ValueObject\ErrorValue;
use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Methods\Concerns\InteractsWithAuthentication;
use Cline\RPC\Methods\Concerns\InteractsWithQueryBuilder;
use Cline\RPC\Methods\Concerns\InteractsWithTransformer;
use Illuminate\Support\Str;
use Override;

use function class_basename;

/**
 * Base class for all JSON-RPC methods with OpenRPC metadata support.
 *
 * Provides core method functionality including authentication helpers, query building,
 * data transformation, and OpenRPC specification metadata. Implements the MethodInterface
 * contract with sensible defaults that can be overridden by subclasses.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractMethod implements MethodInterface
{
    use InteractsWithAuthentication;
    use InteractsWithQueryBuilder;
    use InteractsWithTransformer;

    /**
     * The current JSON-RPC request object.
     */
    protected RequestObjectData $requestObject;

    /**
     * Get the method name for JSON-RPC identification.
     *
     * Generates the method name by converting the class name to snake_case
     * and prefixing it with 'app.'. Override to provide custom method names.
     *
     * @return string The JSON-RPC method name (e.g., 'app.user_list')
     */
    #[Override()]
    public function getName(): string
    {
        return 'app.'.Str::snake(class_basename(static::class));
    }

    /**
     * Get the method summary for OpenRPC documentation.
     *
     * Returns the method name by default. Override to provide a human-readable
     * description of what the method does.
     *
     * @return string A brief summary of the method's purpose
     */
    #[Override()]
    public function getSummary(): string
    {
        return $this->getName();
    }

    /**
     * Get the OpenRPC parameter descriptors for the method.
     *
     * Returns an empty array by default. Override to define the method's
     * expected parameters with their schemas and constraints.
     *
     * @return array<int, \Cline\OpenRpc\ContentDescriptor\ContentDescriptorInterface> Array of parameter descriptors
     */
    #[Override()]
    public function getParams(): array
    {
        return [];
    }

    /**
     * Get the OpenRPC result descriptor for the method.
     *
     * Returns null by default. Override to define the structure of the
     * method's return value for OpenRPC documentation.
     *
     * @return null|ContentDescriptorValue The result descriptor, or null if none specified
     */
    #[Override()]
    public function getResult(): ?ContentDescriptorValue
    {
        return null;
    }

    /**
     * Get the OpenRPC error descriptors for the method.
     *
     * Returns an empty array by default. Override to document possible
     * error responses and their conditions.
     *
     * @return array<int, ErrorValue> Array of error descriptors
     */
    #[Override()]
    public function getErrors(): array
    {
        return [];
    }

    /**
     * Set the current request object for the method.
     *
     * Called before method execution to provide access to request parameters
     * and metadata throughout the method's lifecycle.
     *
     * @param RequestObjectData $requestObject The JSON-RPC request data
     */
    #[Override()]
    public function setRequest(RequestObjectData $requestObject): void
    {
        $this->requestObject = $requestObject;
    }
}
