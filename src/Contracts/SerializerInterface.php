<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Contracts;

use Deprecated;

/**
 * Backward compatibility alias for ProtocolInterface.
 *
 * This interface exists only for backward compatibility. New code should use
 * ProtocolInterface directly. The legacy encode/decode methods are provided
 * for existing code that hasn't migrated to the protocol-based architecture.
 *
 * @author Brian Faust <brian@cline.sh>
 * @deprecated Use ProtocolInterface instead. Will be removed in v2.0.
 */
interface SerializerInterface extends ProtocolInterface
{
    /**
     * Legacy encode method.
     *
     * @param  array<string, mixed> $data Data to encode
     * @return string               Encoded string
     */
    #[Deprecated(message: 'Use encodeRequest() instead')]
    public function encode(array $data): string;

    /**
     * Legacy decode method.
     *
     * @param  string               $data Data to decode
     * @return array<string, mixed> Decoded array
     */
    #[Deprecated(message: 'Use decodeRequest() instead')]
    public function decode(string $data): array;
}
