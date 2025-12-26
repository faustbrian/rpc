<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data;

use Cline\OpenRpc\ContentDescriptor\MethodDataContentDescriptor;

/**
 * Base class for data objects that can generate OpenRPC content descriptors.
 *
 * Provides functionality for automatically generating OpenRPC content descriptor
 * metadata from data class definitions. Content descriptors define the structure
 * and validation rules for method parameters and results in the OpenRPC specification.
 *
 * Child classes automatically inherit the ability to generate their schema
 * definitions for use in API documentation and validation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class AbstractContentDescriptorData extends AbstractData
{
    /**
     * Generate an OpenRPC content descriptor array from this data class.
     *
     * Analyzes the data class structure including properties, types, and
     * validation rules to produce a complete content descriptor definition
     * compatible with the OpenRPC specification.
     *
     * @return array<string, mixed> OpenRPC content descriptor definition
     */
    public static function createContentDescriptor(): array
    {
        return MethodDataContentDescriptor::createFromData(self::class);
    }

    /**
     * Get default content descriptors for this data class.
     *
     * Override this method to provide custom or additional content descriptor
     * definitions that supplement the automatically generated descriptors.
     *
     * @return array<int, mixed> Array of default content descriptors
     */
    protected static function defaultContentDescriptors(): array
    {
        return [];
    }
}
