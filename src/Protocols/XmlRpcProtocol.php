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
use Cline\RPC\Exceptions\XmlRpcRequestDecodingException;
use Cline\RPC\Exceptions\XmlRpcRequestEncodingException;
use Cline\RPC\Exceptions\XmlRpcResponseDecodingException;
use Cline\RPC\Exceptions\XmlRpcResponseEncodingException;
use Deprecated;
use DOMDocument;
use DOMElement;
use Saloon\XmlWrangler\XmlReader;
use Throwable;

use function array_is_list;
use function array_key_exists;
use function array_map;
use function assert;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * XML-RPC protocol implementation.
 *
 * Handles XML-RPC specification message format:
 * Request: <methodCall><methodName>foo</methodName><params>...</params></methodCall>
 * Response: <methodResponse><params><param><value>...</value></param></params></methodResponse>
 * Fault: <methodResponse><fault><value><struct>...</struct></value></fault></methodResponse>
 *
 * @author Brian Faust <brian@cline.sh>
 * @see http://xmlrpc.com/spec.md
 *
 * @psalm-immutable
 */
final readonly class XmlRpcProtocol implements ProtocolInterface, SerializerInterface
{
    /**
     * {@inheritDoc}
     *
     * Transforms internal JSON-RPC structure to XML-RPC methodCall format.
     */
    public function encodeRequest(array $data): string
    {
        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            $methodCall = $dom->createElement('methodCall');
            $dom->appendChild($methodCall);

            // Method name
            $method = $data['method'] ?? '';
            assert(is_string($method));
            $methodName = $dom->createElement('methodName', $method);
            $methodCall->appendChild($methodName);

            // Parameters
            if (array_key_exists('params', $data) && is_array($data['params'])) {
                $params = $dom->createElement('params');
                $methodCall->appendChild($params);

                foreach ($data['params'] as $param) {
                    $paramElement = $dom->createElement('param');
                    $params->appendChild($paramElement);

                    $value = $this->encodeValue($dom, $param);
                    $paramElement->appendChild($value);
                }
            }

            return $dom->saveXML() ?: '';
            // @codeCoverageIgnoreStart
        } catch (Throwable $throwable) {
            throw XmlRpcRequestEncodingException::fromPrevious($throwable);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * {@inheritDoc}
     *
     * Transforms internal response to XML-RPC methodResponse format.
     */
    public function encodeResponse(array $data): string
    {
        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            $methodResponse = $dom->createElement('methodResponse');
            $dom->appendChild($methodResponse);

            // Handle error/fault
            if (array_key_exists('error', $data)) {
                $fault = $dom->createElement('fault');
                $methodResponse->appendChild($fault);

                $error = $data['error'];
                assert(is_array($error));

                $faultValue = $this->encodeValue($dom, [
                    'faultCode' => $error['code'] ?? -32_603,
                    'faultString' => $error['message'] ?? 'Internal error',
                ]);
                $fault->appendChild($faultValue);
            } else {
                // Handle result
                $params = $dom->createElement('params');
                $methodResponse->appendChild($params);

                $param = $dom->createElement('param');
                $params->appendChild($param);

                $value = $this->encodeValue($dom, $data['result'] ?? null);
                $param->appendChild($value);
            }

            return $dom->saveXML() ?: '';
            // @codeCoverageIgnoreStart
        } catch (Throwable $throwable) {
            throw XmlRpcResponseEncodingException::fromPrevious($throwable);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * {@inheritDoc}
     *
     * Parses XML-RPC methodCall to internal JSON-RPC structure.
     */
    public function decodeRequest(string $data): array
    {
        try {
            $reader = XmlReader::fromString($data);
            $parsed = $reader->values();
            assert(is_array($parsed));

            $methodCall = $parsed['methodCall'] ?? $parsed;
            assert(is_array($methodCall));

            $request = [
                'jsonrpc' => '2.0',
                'method' => $methodCall['methodName'] ?? '',
                'params' => [],
            ];

            // Parse params
            if (is_array($methodCall['params'] ?? null) && array_key_exists('param', $methodCall['params'])) {
                $params = $methodCall['params']['param'];
                assert(is_array($params));

                // Single param becomes array
                if (array_key_exists('value', $params)) {
                    $params = [$params];
                }

                foreach ($params as $param) {
                    assert(is_array($param));
                    $request['params'][] = $this->decodeValue($param['value'] ?? null);
                }
            }

            return $request;
        } catch (Throwable $throwable) {
            throw XmlRpcRequestDecodingException::fromPrevious($throwable);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Parses XML-RPC methodResponse to internal structure.
     */
    public function decodeResponse(string $data): array
    {
        try {
            $reader = XmlReader::fromString($data);
            $parsed = $reader->values();
            assert(is_array($parsed));

            $methodResponse = $parsed['methodResponse'] ?? $parsed;
            assert(is_array($methodResponse));

            // Handle fault
            if (is_array($methodResponse['fault'] ?? null) && array_key_exists('fault', $methodResponse)) {
                $faultData = $methodResponse['fault'];
                assert(is_array($faultData));
                $fault = $this->decodeValue($faultData['value'] ?? []);
                assert(is_array($fault));

                return [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => $fault['faultCode'] ?? -32_603,
                        'message' => $fault['faultString'] ?? 'Internal error',
                    ],
                    'id' => null,
                ];
            }

            // Handle result
            $result = null;

            if (is_array($methodResponse['params'] ?? null) && is_array($methodResponse['params']['param'] ?? null)) {
                $param = $methodResponse['params']['param'];
                assert(is_array($param));

                if (array_key_exists('value', $param)) {
                    $result = $this->decodeValue($param['value']);
                }
            }

            return [
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => null,
            ];
        } catch (Throwable $throwable) {
            throw XmlRpcResponseDecodingException::fromPrevious($throwable);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getContentType(): string
    {
        return 'text/xml';
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

    /**
     * Encode a PHP value to XML-RPC <value> element.
     */
    private function encodeValue(DOMDocument $dom, mixed $value): DOMElement
    {
        $valueElement = $dom->createElement('value');

        if (is_int($value)) {
            // Use <i4> as per XML-RPC spec (also supports <int>)
            $typeElement = $dom->createElement('i4', (string) $value);
            $valueElement->appendChild($typeElement);
        } elseif (is_bool($value)) {
            $typeElement = $dom->createElement('boolean', $value ? '1' : '0');
            $valueElement->appendChild($typeElement);
        } elseif (is_float($value)) {
            $typeElement = $dom->createElement('double', (string) $value);
            $valueElement->appendChild($typeElement);
        } elseif (is_string($value)) {
            $typeElement = $dom->createElement('string');
            $typeElement->appendChild($dom->createTextNode($value));
            $valueElement->appendChild($typeElement);
        } elseif (is_array($value)) {
            if (array_is_list($value)) {
                // Array
                $arrayElement = $dom->createElement('array');
                $dataElement = $dom->createElement('data');
                $arrayElement->appendChild($dataElement);

                foreach ($value as $item) {
                    $dataElement->appendChild($this->encodeValue($dom, $item));
                }

                $valueElement->appendChild($arrayElement);
            } else {
                // Struct
                $structElement = $dom->createElement('struct');

                foreach ($value as $key => $val) {
                    $member = $dom->createElement('member');
                    $name = $dom->createElement('name', (string) $key);
                    $member->appendChild($name);
                    $member->appendChild($this->encodeValue($dom, $val));
                    $structElement->appendChild($member);
                }

                $valueElement->appendChild($structElement);
            }
        } else {
            // Null or unknown
            $typeElement = $dom->createElement('string', '');
            $valueElement->appendChild($typeElement);
        }

        return $valueElement;
    }

    /**
     * Decode XML-RPC value array to PHP type.
     */
    private function decodeValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        // Type-specific decoding
        if (array_key_exists('int', $value)) {
            $intValue = $value['int'];

            return is_int($intValue) ? $intValue : (int) $intValue;
        }

        if (array_key_exists('i4', $value)) {
            $i4Value = $value['i4'];

            return is_int($i4Value) ? $i4Value : (int) $i4Value;
        }

        if (array_key_exists('boolean', $value)) {
            return $value['boolean'] === '1' || $value['boolean'] === 1;
        }

        if (array_key_exists('double', $value)) {
            $doubleValue = $value['double'];

            return is_float($doubleValue) ? $doubleValue : (float) $doubleValue;
        }

        if (array_key_exists('string', $value)) {
            $stringValue = $value['string'];

            return is_string($stringValue) ? $stringValue : (string) $stringValue;
        }

        if (array_key_exists('array', $value) && is_array($value['array'] ?? null)) {
            $arrayData = $value['array'];
            assert(is_array($arrayData));

            if (is_array($arrayData['data'] ?? null) && array_key_exists('value', $arrayData['data'])) {
                $values = $arrayData['data']['value'];
                assert(is_array($values));

                // Single value becomes array
                if (array_key_exists('int', $values) || array_key_exists('string', $values) || array_key_exists('boolean', $values)) {
                    $values = [$values];
                }

                return array_map($this->decodeValue(...), $values);
            }
        }

        if (array_key_exists('struct', $value) && is_array($value['struct'] ?? null)) {
            $structData = $value['struct'];
            assert(is_array($structData));

            if (array_key_exists('member', $structData)) {
                $members = $structData['member'];
                assert(is_array($members));

                // Single member becomes array
                if (array_key_exists('name', $members)) {
                    $members = [$members];
                }

                $result = [];

                foreach ($members as $member) {
                    assert(is_array($member));
                    $key = $member['name'] ?? '';
                    assert(is_string($key) || is_int($key));
                    $result[$key] = $this->decodeValue($member['value'] ?? null);
                }

                return $result;
            }
        }

        return $value;
    }
}
