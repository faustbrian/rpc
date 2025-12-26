<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Methods;

// use Ergebnis\Json\Json;
// use Ergebnis\Json\Pointer\JsonPointer;
// use Ergebnis\Json\SchemaValidator\SchemaValidator;
use Cline\OpenRpc\ValueObject\ContentDescriptorValue;
use Cline\OpenRpc\ValueObject\DocumentValue;
use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Contracts\UnwrappedResponseInterface;
// use Cline\RPC\Exceptions\ServerErrorException;
use Cline\RPC\Facades\Server as Facade;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Override;

use function assert;
use function collect;
use function is_array;
use function is_bool;
use function is_numeric;

/**
 * Implements the standard OpenRPC service discovery method.
 *
 * This method provides runtime introspection of the JSON-RPC service by generating
 * a complete OpenRPC 1.3.2 schema document that describes all available methods,
 * their parameters, return types, errors, and server configuration. This enables
 * automatic client generation and interactive API exploration.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://playground.open-rpc.org/
 * @see https://raw.githubusercontent.com/open-rpc/meta-schema/master/schema.json
 * @see https://spec.open-rpc.org/#service-discovery-method
 */
final class DiscoverMethod extends AbstractMethod implements UnwrappedResponseInterface
{
    /**
     * Returns the method name for service discovery.
     *
     * @return string The standard OpenRPC discovery method name
     */
    #[Override()]
    public function getName(): string
    {
        return 'rpc.discover';
    }

    /**
     * Returns a brief description of the discovery method's purpose.
     *
     * @return string Human-readable summary of the method's functionality
     */
    #[Override()]
    public function getSummary(): string
    {
        return 'Returns an OpenRPC schema as a description of this service';
    }

    /**
     * Defines the result schema for the discovery response.
     *
     * The result references the official OpenRPC meta-schema, which defines
     * the structure and validation rules for OpenRPC documents.
     *
     * @return ContentDescriptorValue Schema descriptor referencing the OpenRPC meta-schema
     */
    #[Override()]
    public function getResult(): ContentDescriptorValue
    {
        return ContentDescriptorValue::from([
            'name' => 'OpenRPC Schema',
            'schema' => [
                '$ref' => 'https://raw.githubusercontent.com/open-rpc/meta-schema/master/schema.json',
            ],
        ]);
    }

    /**
     * Generates and returns the complete OpenRPC schema document.
     *
     * Collects all registered methods, content descriptors, and schemas from the
     * server and assembles them into a valid OpenRPC 1.3.2 document. The document
     * includes server information, method definitions, error codes, and reusable
     * component definitions.
     *
     * @return array<string, mixed> Complete OpenRPC schema document
     */
    public function handle(): array
    {
        $errors = $this->buildErrors();

        /** @var array<int, array<string, mixed>> $methods */
        $methods = [];

        /** @var MethodInterface $serverMethod */
        foreach (Facade::getMethodRepository()->all() as $serverMethod) {
            $methods[] = [
                'name' => $serverMethod->getName(),
                'summary' => $serverMethod->getSummary(),
                'params' => $serverMethod->getParams(),
                'result' => $serverMethod->getResult(),
                'errors' => [
                    ...$errors,
                    ...$serverMethod->getErrors(),
                ],
            ];
        }

        $document = self::arr_filter_recursive([
            'openrpc' => '1.3.2',
            'info' => [
                'title' => Facade::getName(),
                'version' => Facade::getVersion(),
                'license' => ['name' => 'Proprietary'],
            ],
            'servers' => [
                [
                    'name' => App::environment(),
                    'url' => URL::to(Facade::getRoutePath()),
                ],
            ],
            'methods' => $methods,
            'components' => [
                'contentDescriptors' => collect(Facade::getContentDescriptors())->keyBy('name'),
                'schemas' => collect(Facade::getSchemas())->keyBy('name'),
                'errors' => collect($errors)->keyBy('message'),
            ],
        ]);

        // FIXME: the JSON Schema 'enum' keyword blows up the validator
        // $this->validateSchema(\json_encode($document, \JSON_THROW_ON_ERROR));

        $result = DocumentValue::from($document)->toArray();
        assert(is_array($result));

        return $result;
    }

    /**
     * Recursively filters an array by removing null and empty values.
     *
     * Traverses the array structure and removes values that evaluate to false,
     * with special handling for boolean false and numeric zero which are preserved.
     * Useful for cleaning the OpenRPC schema by removing optional fields that
     * weren't set.
     *
     * @param  array<string, mixed> $array             Array to filter recursively
     * @param  null|Closure         $callback          Optional callback for custom filtering logic
     * @param  bool                 $removeEmptyArrays Whether to remove arrays that become empty after filtering
     * @return array<string, mixed> Filtered array with empty values removed
     */
    private static function arr_filter_recursive(array $array, ?Closure $callback = null, bool $removeEmptyArrays = false): array
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                /** @phpstan-ignore argument.type */
                $value = self::arr_filter_recursive($value, $callback, $removeEmptyArrays);

                if ($removeEmptyArrays && !(bool) $value) {
                    unset($array[$key]);
                }

                continue;
            }

            if ($callback instanceof Closure && !$callback($value)) {
                unset($array[$key]);

                continue;
            }

            if ((bool) $value) {
                continue;
            }

            if (is_bool($value)) {
                continue;
            }

            if (is_numeric($value) && $value === 0) {
                continue;
            }

            unset($array[$key]);
        }

        unset($value);

        return $array;
    }

    /**
     * Builds the standard error definitions for the OpenRPC schema.
     *
     * Returns all possible JSON-RPC error codes that can be returned by the server,
     * including both standard JSON-RPC 2.0 errors and custom application errors.
     * These error definitions are included in the schema's components section.
     *
     * @return array<int, array{code: int, message: string}> Array of error definitions
     */
    private function buildErrors(): array
    {
        return [
            ['code' => -32_603, 'message' => 'Internal error'],
            ['code' => -32_602, 'message' => 'Invalid fields'],
            ['code' => -32_602, 'message' => 'Invalid filters'],
            ['code' => -32_602, 'message' => 'Invalid params'],
            ['code' => -32_602, 'message' => 'Invalid relationships'],
            ['code' => -32_600, 'message' => 'Invalid Request'],
            ['code' => -32_602, 'message' => 'Invalid sorts'],
            ['code' => -32_601, 'message' => 'Method not found'],
            ['code' => -32_700, 'message' => 'Parse error'],
            ['code' => -32_000, 'message' => 'Server error'],
            ['code' => -32_099, 'message' => 'Server not found'],
        ];
    }

    // private function validateSchema(string $document): void
    // {
    //     $schema = \file_get_contents(__DIR__.'/../../../resources/schema.json');

    //     if ($schema === false) {
    //         throw ServerErrorException::create([
    //             [
    //                 'status' => '418',
    //                 'title' => 'OpenRPC Schema Not Found',
    //                 'detail' => 'The OpenRPC schema could not be loaded.',
    //             ],
    //         ]);
    //     }

    //     $result = (new SchemaValidator())->validate(
    //         Json::fromString($document),
    //         Json::fromString($schema),
    //         JsonPointer::document(),
    //     );

    //     if ($result->isValid()) {
    //         return;
    //     }

    //     /** @var array<\Ergebnis\Json\SchemaValidator\ValidationError> $errors */
    //     $errors = $result->errors();
    //     $errorsTopLevel = $errors[0]->jsonPointer()->toReferenceTokens()[0];

    //     // Ignore the top-level openrpc error
    //     if (\count($errors) === 1 && $errorsTopLevel->toString() === 'openrpc') {
    //         return;
    //     }

    //     throw ServerErrorException::create([
    //         [
    //             'status' => '418',
    //             'title' => 'OpenRPC Schema Validation Failed',
    //             'detail' => 'The OpenRPC schema has failed validation.',
    //             'meta' => [
    //                 'errors' => $errors,
    //             ],
    //         ],
    //     ]);
    // }
}
