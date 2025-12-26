<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Requests;

use Cline\RPC\Contracts\ProtocolInterface;
use Cline\RPC\Contracts\SerializerInterface;
use Cline\RPC\Data\RequestData;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Data\RequestResultData;
use Cline\RPC\Data\ResponseData;
use Cline\RPC\Exceptions\AbstractRequestException;
use Cline\RPC\Exceptions\ExceptionMapper;
use Cline\RPC\Exceptions\ForbiddenException;
use Cline\RPC\Exceptions\InternalErrorException;
use Cline\RPC\Exceptions\InvalidRequestException;
use Cline\RPC\Exceptions\ParseErrorException;
use Cline\RPC\Exceptions\RequestValidationFailedException;
use Cline\RPC\Exceptions\StructurallyInvalidRequestException;
use Cline\RPC\Exceptions\UnauthorizedException;
use Cline\RPC\Facades\Server;
use Cline\RPC\Jobs\CallMethod;
use Cline\RPC\Protocols\JsonRpcProtocol;
use Cline\RPC\Rules\Identifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Throwable;

use function count;
use function data_get;
use function dispatch_sync;
use function is_array;
use function is_string;
use function throw_if;
use function throw_unless;

/**
 * Processes RPC 2.0 requests and dispatches them to registered methods.
 *
 * This handler parses incoming RPC requests, validates their structure,
 * routes them to the appropriate method handlers, and constructs standardized
 * responses. Supports both single requests and batch requests, handles notifications,
 * and provides comprehensive error handling with proper HTTP status codes.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RequestHandler
{
    /**
     * Protocol for decoding RPC request payloads.
     */
    private ProtocolInterface $protocol;

    /**
     * Create a new request handler instance.
     *
     * @param null|ProtocolInterface|SerializerInterface $protocol Protocol for payload decoding (defaults to JSON-RPC)
     */
    public function __construct(ProtocolInterface|SerializerInterface|null $protocol = null)
    {
        $this->protocol = $protocol ?? new JsonRpcProtocol();
    }

    /**
     * Creates a handler result from an array-based request.
     *
     * @param  array<string, mixed>                       $request  Parsed RPC request data
     * @param  null|ProtocolInterface|SerializerInterface $protocol Protocol for payload decoding (defaults to JSON-RPC)
     * @return RequestResultData                          Result containing response data and HTTP status code
     */
    public static function createFromArray(array $request, ProtocolInterface|SerializerInterface|null $protocol = null): RequestResultData
    {
        return new self($protocol)->handle($request);
    }

    /**
     * Creates a handler result from a serialized string request.
     *
     * @param  string                                     $request  Raw RPC request string
     * @param  null|ProtocolInterface|SerializerInterface $protocol Protocol for payload decoding (defaults to JSON-RPC)
     * @return RequestResultData                          Result containing response data and HTTP status code
     */
    public static function createFromString(string $request, ProtocolInterface|SerializerInterface|null $protocol = null): RequestResultData
    {
        return new self($protocol)->handle($request);
    }

    /**
     * Processes an RPC request and returns the result.
     *
     * Handles the complete request lifecycle: parsing, validation, method dispatch,
     * and response construction. Supports both single and batch requests, properly
     * handles notifications (no response), and maps exceptions to appropriate
     * RPC error responses with correct HTTP status codes.
     *
     * @param array<string, mixed>|string $request RPC request as array or serialized string
     *
     * @throws InvalidRequestException When the request structure is invalid
     * @throws ParseErrorException     When deserialization fails
     *
     * @return RequestResultData Result containing response data and HTTP status code
     */
    public function handle(array|string $request): RequestResultData
    {
        try {
            $requestBody = $this->parse($request);

            throw_if(count($requestBody->requestObjects) > 10, StructurallyInvalidRequestException::create([
                [
                    'status' => '400',
                    'source' => ['pointer' => '/'],
                    'title' => 'Invalid request',
                    'detail' => 'The request contains too many items. The maximum is 10.',
                ],
            ]));

            /** @var array<int, Collection|ResponseData> $responses */
            $responses = [];

            foreach ($requestBody->requestObjects as $requestObject) {
                try {
                    $this->validate($requestObject);

                    $requestObject = RequestObjectData::from($requestObject);

                    $method = Server::getMethodRepository()->get($requestObject->method);

                    if ($requestObject->isNotification()) {
                        CallMethod::dispatchAfterResponse($method, $requestObject);

                        // The Server MUST NOT reply to a Notification, including those that are within a batch request.
                        continue;
                    }

                    $responses[] = dispatch_sync(
                        new CallMethod($method, $requestObject),
                    );
                } catch (Throwable $exception) {
                    $responses[] = ResponseData::from([
                        'jsonrpc' => '2.0',
                        'id' => data_get($requestObject, 'id'),
                        'error' => ExceptionMapper::execute($exception)->toError(),
                    ]);
                }
            }

            if (count($responses) < 1) {
                return RequestResultData::from([
                    'data' => $responses,
                    'statusCode' => 200,
                ]);
            }

            if ($requestBody->isBatch) {
                return RequestResultData::from([
                    'data' => $responses,
                    'statusCode' => 200,
                ]);
            }

            return RequestResultData::from([
                'data' => $responses[0],
                'statusCode' => 200,
            ]);
        } catch (Throwable $throwable) {
            if ($throwable instanceof AbstractRequestException) {
                return RequestResultData::from([
                    'data' => ResponseData::createFromRequestException($throwable),
                    'statusCode' => 400,
                ]);
            }

            // @codeCoverageIgnoreStart
            if ($throwable instanceof AuthenticationException) {
                return RequestResultData::from([
                    'data' => ResponseData::createFromRequestException(UnauthorizedException::create()),
                    'statusCode' => 401,
                ]);
            }

            if ($throwable instanceof AuthorizationException) {
                return RequestResultData::from([
                    'data' => ResponseData::createFromRequestException(ForbiddenException::create()),
                    'statusCode' => 403,
                ]);
            }

            return RequestResultData::from([
                'data' => ResponseData::createFromRequestException(
                    InternalErrorException::create($throwable),
                ),
                'statusCode' => 500,
            ]);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Validates a request object against JSON-RPC 2.0 specification.
     *
     * Ensures the request contains required fields (jsonrpc, method) with correct
     * types and values. The id field is optional (notifications omit it).
     *
     * @param mixed $data Request object to validate
     *
     * @throws InvalidRequestException When validation fails
     */
    private function validate(mixed $data): void
    {
        throw_unless(is_array($data), StructurallyInvalidRequestException::create());

        $validator = Validator::make(
            $data,
            [
                'jsonrpc' => ['required', 'in:2.0'],
                'id' => new Identifier(),
                'method' => ['required', 'string'],
                'params' => ['nullable', 'array'],
            ],
        );

        throw_if($validator->fails(), RequestValidationFailedException::fromValidator($validator));
    }

    /**
     * Parses and normalizes the request into a RequestData object.
     *
     * Deserializes strings using the configured serializer, validates the structure,
     * and determines whether the request is a single request or batch request based
     * on array structure.
     *
     * @param array<string, mixed>|string $requestObjects Raw request data as array or serialized string
     *
     * @throws InvalidRequestException When the request structure is invalid or empty
     * @throws ParseErrorException     When deserialization fails
     *
     * @return RequestData Normalized request data with batch flag
     */
    private function parse(array|string $requestObjects): RequestData
    {
        if (is_string($requestObjects)) {
            try {
                $requestObjects = $this->protocol->decodeRequest($requestObjects);
            } catch (Throwable) {
                throw ParseErrorException::create();
            }
        }

        throw_if($requestObjects === [], StructurallyInvalidRequestException::create());

        // Single request if array is associative, batch if numeric
        if (Arr::isAssoc($requestObjects)) {
            return RequestData::from([
                'requestObjects' => [$requestObjects],
                'isBatch' => false,
            ]);
        }

        return RequestData::from([
            'requestObjects' => $requestObjects,
            'isBatch' => true,
        ]);
    }
}
