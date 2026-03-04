<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\RPC\Exceptions\XmlRpcDecodingException;
use Cline\RPC\Protocols\XmlRpcProtocol;

describe('XmlRpcProtocol', function (): void {
    beforeEach(function (): void {
        $this->protocol = new XmlRpcProtocol();
    });

    describe('Request Encoding', function (): void {
        test('encodes simple method call', function (): void {
            // Arrange
            $request = [
                'method' => 'examples.getStateName',
                'params' => [41],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<methodCall>');
            expect($xml)->toContain('<methodName>examples.getStateName</methodName>');
            expect($xml)->toContain('<params>');
            expect($xml)->toContain('<param>');
            expect($xml)->toContain('<i4>41</i4>');
        });

        test('encodes method with multiple parameters', function (): void {
            // Arrange
            $request = [
                'method' => 'test.sumprod',
                'params' => [2, 3],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<methodCall>');
            expect($xml)->toContain('<methodName>test.sumprod</methodName>');
            expect($xml)->toContain('<i4>2</i4>');
            expect($xml)->toContain('<i4>3</i4>');
        });

        test('encodes method with struct parameter', function (): void {
            // Arrange
            $request = [
                'method' => 'user.create',
                'params' => [
                    ['name' => 'Alice', 'age' => 30],
                ],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<struct>');
            expect($xml)->toContain('<member>');
            expect($xml)->toContain('<name>name</name>');
            expect($xml)->toContain('<string>Alice</string>');
            expect($xml)->toContain('<name>age</name>');
            expect($xml)->toContain('<i4>30</i4>');
        });

        test('encodes method with array parameter', function (): void {
            // Arrange
            $request = [
                'method' => 'test.sum',
                'params' => [[1, 2, 3, 4]],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<array>');
            expect($xml)->toContain('<data>');
            expect($xml)->toContain('<i4>1</i4>');
            expect($xml)->toContain('<i4>2</i4>');
            expect($xml)->toContain('<i4>3</i4>');
        });

        test('encodes boolean values correctly', function (): void {
            // Arrange
            $request = [
                'method' => 'test.bool',
                'params' => [true, false],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<boolean>1</boolean>');
            expect($xml)->toContain('<boolean>0</boolean>');
        });

        test('encodes string values with XML escaping', function (): void {
            // Arrange
            $request = [
                'method' => 'test.echo',
                'params' => ['<tag>value & more</tag>'],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<methodName>test.echo</methodName>');
            expect($xml)->not->toContain('<tag>value & more</tag>'); // Should be escaped
        });

        test('encodes double/float values', function (): void {
            // Arrange
            $request = [
                'method' => 'test.pi',
                'params' => [3.141_59],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<double>3.14159</double>');
        });
    });

    describe('Response Encoding', function (): void {
        test('encodes successful response', function (): void {
            // Arrange
            $response = [
                'result' => 'South Dakota',
            ];

            // Act
            $xml = $this->protocol->encodeResponse($response);

            // Assert
            expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
            expect($xml)->toContain('<methodResponse>');
            expect($xml)->toContain('<params>');
            expect($xml)->toContain('<param>');
            expect($xml)->toContain('<string>South Dakota</string>');
            expect($xml)->toContain('</methodResponse>');
        });

        test('encodes response with struct result', function (): void {
            // Arrange
            $response = [
                'result' => ['userId' => 123, 'username' => 'alice'],
            ];

            // Act
            $xml = $this->protocol->encodeResponse($response);

            // Assert
            expect($xml)->toContain('<methodResponse>');
            expect($xml)->toContain('<struct>');
            expect($xml)->toContain('<name>userId</name>');
            expect($xml)->toContain('<i4>123</i4>');
            expect($xml)->toContain('<name>username</name>');
            expect($xml)->toContain('<string>alice</string>');
        });

        test('encodes response with array result', function (): void {
            // Arrange
            $response = [
                'result' => [5, 6],
            ];

            // Act
            $xml = $this->protocol->encodeResponse($response);

            // Assert
            expect($xml)->toContain('<methodResponse>');
            expect($xml)->toContain('<array>');
            expect($xml)->toContain('<i4>5</i4>');
            expect($xml)->toContain('<i4>6</i4>');
        });

        test('encodes fault response', function (): void {
            // Arrange
            $response = [
                'error' => [
                    'code' => 4,
                    'message' => 'Too many parameters.',
                ],
            ];

            // Act
            $xml = $this->protocol->encodeResponse($response);

            // Assert
            expect($xml)->toContain('<methodResponse>');
            expect($xml)->toContain('<fault>');
            expect($xml)->toContain('<struct>');
            expect($xml)->toContain('<name>faultCode</name>');
            expect($xml)->toContain('<i4>4</i4>');
            expect($xml)->toContain('<name>faultString</name>');
            expect($xml)->toContain('<string>Too many parameters.</string>');
        });
    });

    describe('Request Decoding', function (): void {
        test('decodes simple method call', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>examples.getStateName</methodName>
  <params>
    <param><value><i4>41</i4></value></param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result)->toBeArray();
            expect($result['jsonrpc'])->toBe('2.0');
            expect($result['method'])->toBe('examples.getStateName');
            expect($result['params'])->toBe([41]);
        });

        test('decodes method with multiple params', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test.sumprod</methodName>
  <params>
    <param><value><i4>2</i4></value></param>
    <param><value><i4>3</i4></value></param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['method'])->toBe('test.sumprod');
            expect($result['params'])->toBe([2, 3]);
        });

        test('decodes struct parameter', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>user.create</methodName>
  <params>
    <param>
      <value>
        <struct>
          <member>
            <name>name</name>
            <value><string>Alice</string></value>
          </member>
          <member>
            <name>age</name>
            <value><i4>30</i4></value>
          </member>
        </struct>
      </value>
    </param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['method'])->toBe('user.create');
            expect($result['params'][0]['name'])->toBe('Alice');
            expect($result['params'][0]['age'])->toBe(30);
        });

        test('decodes array parameter', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test.sum</methodName>
  <params>
    <param>
      <value>
        <array>
          <data>
            <value><i4>1</i4></value>
            <value><i4>2</i4></value>
            <value><i4>3</i4></value>
          </data>
        </array>
      </value>
    </param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['method'])->toBe('test.sum');
            expect($result['params'][0])->toBe([1, 2, 3]);
        });

        test('decodes boolean values', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test.bool</methodName>
  <params>
    <param><value><boolean>1</boolean></value></param>
    <param><value><boolean>0</boolean></value></param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['params'])->toBe([true, false]);
        });

        test('decodes double values', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test.pi</methodName>
  <params>
    <param><value><double>3.14159</double></value></param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['params'][0])->toBe(3.141_59);
        });
    });

    describe('Response Decoding', function (): void {
        test('decodes successful response', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodResponse>
  <params>
    <param>
      <value><string>South Dakota</string></value>
    </param>
  </params>
</methodResponse>';

            // Act
            $result = $this->protocol->decodeResponse($xml);

            // Assert
            expect($result)->toBeArray();
            expect($result['jsonrpc'])->toBe('2.0');
            expect($result['result'])->toBe('South Dakota');
        });

        test('decodes struct result', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodResponse>
  <params>
    <param>
      <value>
        <struct>
          <member>
            <name>userId</name>
            <value><i4>123</i4></value>
          </member>
          <member>
            <name>username</name>
            <value><string>alice</string></value>
          </member>
        </struct>
      </value>
    </param>
  </params>
</methodResponse>';

            // Act
            $result = $this->protocol->decodeResponse($xml);

            // Assert
            expect($result['result']['userId'])->toBe(123);
            expect($result['result']['username'])->toBe('alice');
        });

        test('decodes fault response', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><i4>4</i4></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>Too many parameters.</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>';

            // Act
            $result = $this->protocol->decodeResponse($xml);

            // Assert
            expect($result['error']['code'])->toBe(4);
            expect($result['error']['message'])->toBe('Too many parameters.');
        });
    });

    describe('Content Type', function (): void {
        test('returns correct content type', function (): void {
            // Act
            $contentType = $this->protocol->getContentType();

            // Assert
            expect($contentType)->toBe('text/xml');
        });
    });

    describe('Round Trip', function (): void {
        test('round trip encoding and decoding preserves data', function (): void {
            // Arrange
            $originalRequest = [
                'method' => 'test.echo',
                'params' => [
                    'Hello World',
                    42,
                    true,
                    ['key' => 'value'],
                ],
            ];

            // Act
            $encoded = $this->protocol->encodeRequest($originalRequest);
            $decoded = $this->protocol->decodeRequest($encoded);

            // Assert
            expect($decoded['method'])->toBe($originalRequest['method']);
            expect($decoded['params'][0])->toBe('Hello World');
            expect($decoded['params'][1])->toBe(42);
            expect($decoded['params'][2])->toBe(true);
            expect($decoded['params'][3]['key'])->toBe('value');
        });
    });

    describe('Sad Paths', function (): void {
        test('decodeRequest throws exception on invalid XML', function (): void {
            // Arrange
            $invalidXml = '<invalid>xml';

            // Act & Assert
            expect(fn (): array => $this->protocol->decodeRequest($invalidXml))
                ->toThrow(XmlRpcDecodingException::class);
        });

        test('decodeResponse throws exception on invalid XML', function (): void {
            // Arrange
            $invalidXml = '<invalid>xml';

            // Act & Assert
            expect(fn (): array => $this->protocol->decodeResponse($invalidXml))
                ->toThrow(XmlRpcDecodingException::class);
        });
    });

    describe('Deprecated Methods', function (): void {
        test('encode method calls encodeRequest', function (): void {
            // Arrange
            $data = ['method' => 'test'];

            // Act
            $result = $this->protocol->encode($data);

            // Assert
            expect($result)->toBe($this->protocol->encodeRequest($data));
        });

        test('decode method calls decodeRequest', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test</methodName>
</methodCall>';

            // Act
            $result = $this->protocol->decode($xml);

            // Assert
            expect($result)->toBe($this->protocol->decodeRequest($xml));
        });
    });

    describe('Edge Cases', function (): void {
        test('encodes null value as empty string', function (): void {
            // Arrange
            $request = [
                'method' => 'test.null',
                'params' => [null],
            ];

            // Act
            $xml = $this->protocol->encodeRequest($request);

            // Assert
            expect($xml)->toContain('<string/>');
        });

        test('encodes response with null result', function (): void {
            // Arrange
            $response = ['result' => null];

            // Act
            $xml = $this->protocol->encodeResponse($response);

            // Assert
            expect($xml)->toContain('<string/>');
        });

        test('decodes int type value', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test.int</methodName>
  <params>
    <param><value><int>99</int></value></param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['params'][0])->toBe(99);
        });

        test('decodes single struct member', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodResponse>
  <params>
    <param>
      <value>
        <struct>
          <member>
            <name>key</name>
            <value><string>value</string></value>
          </member>
        </struct>
      </value>
    </param>
  </params>
</methodResponse>';

            // Act
            $result = $this->protocol->decodeResponse($xml);

            // Assert
            expect($result['result']['key'])->toBe('value');
        });

        test('decodes single value in array correctly', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test</methodName>
  <params>
    <param>
      <value>
        <array>
          <data>
            <value><string>single</string></value>
          </data>
        </array>
      </value>
    </param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['params'][0])->toBe(['single']);
        });

        test('encodes response with fault code and message defaults', function (): void {
            // Arrange
            $response = ['error' => []];

            // Act
            $xml = $this->protocol->encodeResponse($response);

            // Assert
            expect($xml)->toContain('<name>faultCode</name>');
            expect($xml)->toContain('<i4>-32603</i4>');
            expect($xml)->toContain('<name>faultString</name>');
            expect($xml)->toContain('<string>Internal error</string>');
        });

        test('decodes response with fault defaults', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodResponse>
  <fault>
    <value>
      <struct>
      </struct>
    </value>
  </fault>
</methodResponse>';

            // Act
            $result = $this->protocol->decodeResponse($xml);

            // Assert
            expect($result['error']['code'])->toBe(-32_603);
            expect($result['error']['message'])->toBe('Internal error');
        });

        test('decodes response without params value', function (): void {
            // Arrange
            $xml = '<?xml version="1.0"?>
<methodResponse>
  <params>
    <param>
    </param>
  </params>
</methodResponse>';

            // Act
            $result = $this->protocol->decodeResponse($xml);

            // Assert
            expect($result['result'])->toBeNull();
        });

        test('decodes non-array value directly', function (): void {
            // Arrange - create XML with plain text value (not wrapped in type tag)
            $xml = '<?xml version="1.0"?>
<methodCall>
  <methodName>test</methodName>
  <params>
    <param><value>plain text</value></param>
  </params>
</methodCall>';

            // Act
            $result = $this->protocol->decodeRequest($xml);

            // Assert
            expect($result['params'][0])->toBe('plain text');
        });
    });
});
