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
class ClientSafeTraitTest extends \PHPUnit\Framework\TestCase {

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

    public function testRequestsSafe() {
        $cl = $this->prepare([
            new Response(200, [], 'NormalBody'),
            new Response(401, [], 'FailedBody'),
            new Response(200, [], 'RepeatedBody'),
            new Response(200, [], 'NormalBody'),
            new Response(401, [], 'FailedBody'),
            new Response(200, [], 'RepeatedBody'),
        ]);

        $response = $cl->pubRequestSafe('POST', '/test');
        $this->assertEquals(0, $cl->getRelogins());
        $this->assertEquals('NormalBody', (string) $response->getBody());

        $response = $cl->pubRequestSafe('POST', '/test');
        $this->assertEquals(1, $cl->getRelogins());
        $this->assertEquals('RepeatedBody', (string) $response->getBody());

        $request = new Request('POST', '/test');
        $response = $cl->pubSendSafe($request);
        $this->assertEquals(1, $cl->getRelogins());
        $this->assertEquals('NormalBody', (string) $response->getBody());

        $response = $cl->pubSendSafe($request);
        $this->assertEquals(2, $cl->getRelogins());
        $this->assertEquals('RepeatedBody', (string) $response->getBody());
    }

    public function testRequestsSafeNoSession() {
        $cl = $this->prepare(
                [
                    new Response(200, [], 'NormalBody'),
                    new Response(200, [], 'NormalBody'),
                ],
                false);

        $response = $cl->pubRequestSafe('POST', '/test');
        $this->assertEquals(1, $cl->getRelogins());
        $this->assertEquals('NormalBody', (string) $response->getBody());

        $request = new Request('POST', '/test');
        $response = $cl->pubSendSafe($request);
        $this->assertEquals(2, $cl->getRelogins());
        $this->assertEquals('NormalBody', (string) $response->getBody());
    }

    public function testSendSafeException_1() {
        $cl = $this->prepare([new Response(504, [], 'ErrorBody', '1.1', 'Reason')]);

        $rq = new Request('POST', '/testLocation', [], 'TestBody');
        $this->expectException(PortaException::class);
        $this->expectExceptionMessage('Reason');
        $this->expectExceptionCode(504);
        $cl->pubSendSafe($rq);
    }

    public function testSendSafeException_2() {
        $cl = $this->prepare([new Response(505, [], 'ErrorBody', '1.1', 'Reason2')], false);

        $rq = new Request('POST', '/testLocation', [], 'TestBody');
        $this->expectException(PortaException::class);
        $this->expectExceptionMessage('Reason2');
        $this->expectExceptionCode(505);
        $response = $cl->pubSendSafe($rq);
    }

    public function testRequestSafeException_1() {
        $cl = $this->prepare([new Response(502, [], 'ErrorBody', '1.1', 'Reason')]);

        $this->expectException(PortaException::class);
        $this->expectExceptionMessage('Reason');
        $this->expectExceptionCode(502);
        $cl->pubRequestSafe('POST');
    }

    public function testRequestSafeException_2() {
        $cl = $this->prepare([new Response(503, [], 'ErrorBody', '1.1', 'Reason2')], false);

        $this->expectException(PortaException::class);
        $this->expectExceptionMessage('Reason2');
        $this->expectExceptionCode(503);
        $cl->pubRequestSafe('POST');
    }

    protected function prepare(array $answers, $sessonUp = true) {
        $mock = new MockHandler($answers);
        $handlerStack = HandlerStack::create($mock);
        $this->container = [];
        $handlerStack->push(Middleware::history($this->container));

        return new ClientSafeTraitWrap(['handler' => $handlerStack], $sessonUp);
    }

    protected function getRequst($index): ?Request {
        return $this->container[$index]['request'] ?? null;
    }

    protected function getOptions($index): ?array {
        return $this->container[$index]['options'] ?? null;
    }

}
