<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Jobs;

use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Contracts\UnwrappedResponseInterface;
use Cline\RPC\Data\RequestObjectData;
use Cline\RPC\Data\ResponseData;
use Cline\RPC\Exceptions\ExceptionMapper;
use Cline\RPC\Exceptions\InvalidDataException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionNamedType;
use Spatie\LaravelData\Data;
use Throwable;

use function array_filter;
use function call_user_func;
use function count;
use function is_subclass_of;

/**
 * Job that executes a JSON-RPC method with automatic error handling and response formatting.
 *
 * Orchestrates method invocation by resolving parameters, handling Data object validation,
 * and wrapping results in proper JSON-RPC response format. Catches all exceptions and
 * converts them to standardized JSON-RPC error responses.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class CallMethod
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new method execution job instance.
     *
     * @param MethodInterface   $method        the JSON-RPC method to execute, containing the business
     *                                         logic and parameter requirements for the requested operation
     * @param RequestObjectData $requestObject the parsed JSON-RPC request containing method name,
     *                                         parameters, and metadata required for execution
     */
    public function __construct(
        private MethodInterface $method,
        private RequestObjectData $requestObject,
    ) {}

    /**
     * Execute the JSON-RPC method and return the formatted response.
     *
     * Resolves method parameters from the request, invokes the method handler,
     * and wraps the result in a JSON-RPC 2.0 response structure. Any exceptions
     * thrown during execution are caught and converted to error responses.
     *
     * @throws InvalidDataException When Data object validation fails during parameter resolution
     *
     * @return array|ResponseData The method result wrapped in a JSON-RPC response,
     *                            or a raw array for unwrapped responses
     */
    public function handle(): array|ResponseData
    {
        try {
            $this->method->setRequest($this->requestObject);

            $result = App::call(
                // @phpstan-ignore-next-line
                [$this->method, 'handle'],
                [
                    'requestObject' => $this->requestObject,
                    ...$this->resolveParameters($this->method, (array) $this->requestObject->getParam('data')),
                ],
            );

            if ($this->method instanceof UnwrappedResponseInterface) {
                /** @var array $result */
                return $result;
            }

            return ResponseData::from([
                'jsonrpc' => $this->requestObject->jsonrpc,
                'id' => $this->requestObject->id,
                'result' => $result,
            ]);
        } catch (Throwable $throwable) {
            return ResponseData::from([
                'jsonrpc' => '2.0',
                'id' => $this->requestObject->id,
                'error' => ExceptionMapper::execute($throwable)->toError(),
            ]);
        }
    }

    /**
     * Resolve method parameters from the request data.
     *
     * Maps request parameters to method signature parameters, handling Data object
     * instantiation, snake_case to camelCase conversion, and type coercion. Filters
     * out internal parameters and validates Data objects.
     *
     * @param MethodInterface      $method The method whose parameters need resolution
     * @param array<string, mixed> $params The raw request parameters to resolve
     *
     * @throws InvalidDataException When Data object validation fails
     *
     * @return array<string, mixed> The resolved parameters keyed by parameter name
     */
    private function resolveParameters(MethodInterface $method, array $params): array
    {
        if (count($params) < 1) {
            return [];
        }

        $parameters = new ReflectionClass($method)->getMethod('handle')->getParameters();
        $parametersMapped = [];

        foreach ($parameters as $parameter) {
            $parameterName = $parameter->getName();

            // This is an internal parameter, we don't want to map it.
            if ($parameterName === 'requestObject') {
                continue;
            }

            $parameterType = $parameter->getType();

            if ($parameterType instanceof ReflectionNamedType) {
                $parameterType = $parameterType->getName();
            }

            $parameterValue = Arr::get($params, $parameterName) ?? Arr::get($params, Str::snake($parameterName, '.'));

            if (is_subclass_of((string) $parameterType, Data::class)) {
                try {
                    $parametersMapped[$parameterName] = call_user_func(
                        [(string) $parameterType, 'validateAndCreate'],
                        $parameter->getName() === 'data' ? $params : $parameterValue,
                    );
                } catch (ValidationException $exception) {
                    throw InvalidDataException::create($exception);
                }
            } elseif ($parameterType === 'array' && $parameter->getName() === 'data') {
                $parametersMapped[$parameterName] = $params;
            } else {
                $parametersMapped[$parameterName] = $parameterValue;
            }
        }

        return array_filter($parametersMapped);
    }
}
