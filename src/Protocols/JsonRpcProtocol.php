<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Protocols;

use Cline\RPC\Contracts\ProtocolInterface;
use Cline\RPC\Contracts\SerializerInterface;
use Deprecated;

use const JSON_THROW_ON_ERROR;

use function assert;
use function is_array;
use function json_decode;
use function json_encode;

/**
 * JSON-RPC 2.0 protocol implementation.
 *
 * Handles JSON-RPC 2.0 specification message format with structure:
 * Request: {"jsonrpc":"2.0","method":"foo","params":[...],"id":1}
 * Response: {"jsonrpc":"2.0","result":...,"id":1}
 * Error: {"jsonrpc":"2.0","error":{"code":-32600,"message":"..."},"id":1}
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://www.jsonrpc.org/specification
 *
 * @psalm-immutable
 */
final readonly class JsonRpcProtocol implements ProtocolInterface, SerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function encodeRequest(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * {@inheritDoc}
     */
    public function encodeResponse(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * {@inheritDoc}
     */
    public function decodeRequest(string $data): array
    {
        $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($decoded));

        return $decoded;
    }

    /**
     * {@inheritDoc}
     */
    public function decodeResponse(string $data): array
    {
        $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($decoded));

        return $decoded;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentType(): string
    {
        return 'application/json';
    }

    #[Deprecated(message: 'Use encodeRequest() instead')]
    public function encode(array $data): string
    {
        return $this->encodeRequest($data);
    }

    #[Deprecated(message: 'Use decodeRequest() instead')]
    public function decode(string $data): array
    {
        return $this->decodeRequest($data);
    }
}
