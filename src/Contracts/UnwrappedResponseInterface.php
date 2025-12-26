<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Contracts;

/**
 * Marker interface for responses that should bypass document wrapping.
 *
 * Implementing this interface signals that a response object should be
 * returned directly without wrapping in a DocumentData envelope. This is
 * useful for methods that need to return raw data structures or when the
 * response already conforms to a specific format.
 *
 * By default, all RPC responses are wrapped in a DocumentData structure
 * following JSON:API conventions. This interface provides an escape hatch
 * for responses that need to maintain their raw structure.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface UnwrappedResponseInterface
{
    // Marker interface with no methods - used for type detection only
}
