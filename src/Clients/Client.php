<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Clients;

use Cline\RPC\Contracts\ProtocolInterface;
use Cline\RPC\Contracts\SerializerInterface;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Data\ResponseData;
use Cline\RPC\Protocols\JsonRpcProtocol;
use Cline\RPC\Protocols\XmlRpcProtocol;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;

use function count;

/**
 * JSON-RPC 2.0 HTTP client for making single and batch requests.
 *
 * Provides a fluent interface for building and executing JSON-RPC requests
 * against a remote server. Supports both single request and batch request
 * patterns as defined in the JSON-RPC 2.0 specification.
 *
 * ```php
 * $client = Client::create('https://api.example.com');
 * $response = $client->add($request)->request();
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Client
{
    /**
     * Batch of request objects to be sent.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $batch = [];

    /**
     * HTTP client configured with base URL and content-type headers.
     */
    private readonly PendingRequest $client;

    /**
     * Protocol for encoding/decoding RPC payloads.
     */
    private readonly ProtocolInterface $protocol;

    /**
     * Create a new RPC client instance.
     *
     * @param string                                     $host     Base URL of the RPC server endpoint
     * @param null|ProtocolInterface|SerializerInterface $protocol Protocol for payload encoding (defaults to JSON-RPC)
     */
    public function __construct(
        string $host,
        ProtocolInterface|SerializerInterface|null $protocol = null,
    ) {
        $this->protocol = $protocol ?? new JsonRpcProtocol();
        $this->client = Http::baseUrl($host)
            ->withHeaders([
                'Content-Type' => $this->protocol->getContentType(),
                'Accept' => $this->protocol->getContentType(),
            ]);
    }

    /**
     * Create a new client instance using static factory pattern.
     *
     * @param string                                     $host     Base URL of the RPC server endpoint
     * @param null|ProtocolInterface|SerializerInterface $protocol Protocol for payload encoding (defaults to JSON-RPC)
     */
    public static function create(string $host, ProtocolInterface|SerializerInterface|null $protocol = null): self
    {
        return new self($host, $protocol);
    }

    /**
     * Create a JSON-RPC 2.0 client.
     *
     * Convenience factory method for creating a client with JSON-RPC protocol.
     *
     * @param string $host Base URL of the RPC server endpoint
     */
    public static function json(string $host): self
    {
        return new self($host, new JsonRpcProtocol());
    }

    /**
     * Create an XML-RPC client.
     *
     * Convenience factory method for creating a client with XML-RPC protocol.
     *
     * @param string $host Base URL of the RPC server endpoint
     */
    public static function xml(string $host): self
    {
        return new self($host, new XmlRpcProtocol());
    }

    /**
     * Add a single request to the batch queue.
     *
     * @param RequestObjectData $request Request object containing method, params, and ID
     */
    public function add(RequestObjectData $request): self
    {
        $this->batch[] = $request->jsonSerialize();

        return $this;
    }

    /**
     * Add multiple requests to the batch queue for batch processing.
     *
     * @param list<RequestObjectData> $requests Array of request objects to execute
     */
    public function addMany(array $requests): self
    {
        foreach ($requests as $request) {
            $this->add($request);
        }

        return $this;
    }

    /**
     * Execute the queued request(s) and return response(s).
     *
     * Automatically detects single vs batch requests based on queue size.
     * Batch requests return a collection of responses, single requests
     * return a single response object.
     *
     * @return DataCollection<int, ResponseData>|ResponseData Collection for batch requests, single response otherwise
     */
    public function request(): DataCollection|ResponseData
    {
        $httpResponse = $this->client->post(
            '/',
            $this->isBatch() ? $this->batch : $this->batch[0],
        );

        $response = $this->protocol->decodeResponse($httpResponse->body());

        if ($this->isBatch()) {
            return ResponseData::collect($response, DataCollection::class);
        }

        return ResponseData::from($response);
    }

    /**
     * Determine if the current batch contains multiple requests.
     *
     * @return bool True if batch contains more than one request
     */
    private function isBatch(): bool
    {
        return count($this->batch) > 1;
    }
}
