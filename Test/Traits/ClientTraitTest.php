<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Traits;

use PortaApi\Exceptions\PortaException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * Description of GuzzleTraitTest
 *
 */
class ClientTraitTest extends \PHPUnit\Framework\TestCase {

    const DATA = [
        'TestKey1' => 'TestVal1',
        'TestKey2' => 'TestVal2',
        'TestKey3' =>
        [
            'TestKey31' => 'TestVal31',
            'TestKey32' => 'TestVal32',
        ],
        'TestValNum0',
    ];

    protected $container = [];

    public function testSend() {
        $cl = $this->prepare([
            new Response(200, [], 'TestBodyString'),
            new \GuzzleHttp\Exception\ConnectException('NetworkError', new Request('GET', '/'))
        ]);

        $rq = new Request('POST', '/testLocation', [], 'TestBody');
        $response = $cl->pubSend($rq);
        $request = $this->getRequst(0);
        $this->assertEquals('https://testhost.dom/test/testLocation', (string) $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('Bearer TokenStringHere', $request->getHeader('Authorization')[0]);
        $this->expectException(\PortaApi\Exceptions\PortaConnectException::class);
        $cl->pubSend($rq);
    }

    public function testRequest() {
        $cl = $this->prepare([
            new Response(200, [], 'TestBodyString'),
            new \GuzzleHttp\Exception\ConnectException('NetworkError', new Request('GET', '/'))
        ]);

        $response = $cl->pubRequest('POST', '/testRequest', ['verify' => false, 'json' => self::DATA,]);
        $this->assertInstanceOf(Response::class, $response);
        $request = $this->getRequst(0);
        $this->assertEquals('https://testhost.dom/test/testRequest', (string) $request->getUri());
        $this->assertEquals('Bearer TokenStringHere', $request->getHeader('Authorization')[0]);
        $this->assertFalse($this->getOptions(0)['http_errors']);
        $this->assertEquals(json_encode(self::DATA), $request->getBody());

        $this->expectException(\PortaApi\Exceptions\PortaConnectException::class);
        $this->expectExceptionMessage('NetworkError');
        $cl->pubRequest('POST', '/testRequest');
    }

    public function testJson() {
        $this->assertEquals(self::DATA, ClientTraitWrap::jsonResponse(new Response(200, [], json_encode(self::DATA))));
        $this->expectException(PortaException::class);
        ClientTraitWrap::jsonResponse(new Response(200, [], 'NotJSON'));
    }

    public function testHostCheck() {
        $this->expectException(\PortaApi\Exceptions\PortaException::class);
        $c = new ClientTraitWrap();
        $c->pubTestSetup();
    }

    public function testTimeConverterts() {
        $testTime = "2023-02-12 10:20:30";
        $to = ClientTraitWrap::timeToLocal($testTime, '+300');
        $this->assertEquals("2023-02-12 13:20:30", $to->format('Y-m-d H:i:s'));
        $to = new \DateTime("2023-02-12 07:20:30", new \DateTimeZone("-300"));
        $this->assertEquals($testTime, ClientTraitWrap::timeToBilling($to));
    }

    protected function prepare(array $answers, $sessonUp = true) {
        $mock = new MockHandler($answers);
        $handlerStack = HandlerStack::create($mock);
        $this->container = [];
        $handlerStack->push(Middleware::history($this->container));

        return new ClientTraitWrap(['handler' => $handlerStack], $sessonUp);
    }

    protected function getRequst($index): ?Request {
        return $this->container[$index]['request'] ?? null;
    }

    protected function getOptions($index): ?array {
        return $this->container[$index]['options'] ?? null;
    }

}
