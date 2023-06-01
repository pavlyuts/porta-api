<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Components;

use Porta\Billing\Components\SessionClient;
use Porta\Billing\Components\SessionManager;
use Porta\Billing\PortaConfig;
use PortaApiTest\Tools\SessionPHPClassStorage;
use PortaApiTest\Tools\PortaToken;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions as RO;

/**
 * Tests for SessionClient
 */
class SessionClientTest extends \PortaApiTest\Tools\RequestTestCase {

    public function testRequest() {
        $conf = (new PortaConfig('testhost.dom'))
                ->setOptions(
                $this->prepareRequests([
                    new Response(200, [], 'success'),
                    new Response(200, [], 'success'),
                    new \GuzzleHttp\Exception\ConnectException("Guzzle Exception Message", new \GuzzleHttp\Psr7\Request('GET', '')),
                ])
        );
        $session = $this->createMock(SessionManager::class);
        $session->expects($this->any())
                ->method('getAuthHeader')
//                ->willReturn(['Authorization' => 1], ['Authorization' => 2], ['Authorization' => 3], ['Authorization' => 4], ['Authorization' => 5]);
                ->willReturn([], ['Authorization' => 'Bearer TokenContent'], []);
        $sessonData = PortaToken::createLoginData(7200);
        $storage = new SessionPHPClassStorage($sessonData);
        $c = new SessionClient($conf);
        $c->setSesson($session);

        $response = $c->request('POST', '/Test/get_test', [RO::BODY => 'RequestBody']);
        $request = $this->getRequst(0);
        $this->assertEquals('https://testhost.dom/Test/get_test', (string) $request->getUri());
        //var_export($request->getHeader('Authorization'));
        $this->assertEquals([], $request->getHeader('Authorization'));
        $this->assertFalse($this->getOptions(0)['http_errors']);
        $this->assertEquals('RequestBody', $request->getBody());

        $response = $c->request('POST', '/Test/get_test', []);
        $request = $this->getRequst(1);
        $this->assertEquals('https://testhost.dom/Test/get_test', (string) $request->getUri());
        $this->assertEquals('Bearer TokenContent', $request->getHeader('Authorization')[0]);
        //var_export($request->getHeader('Authorization'));
        $this->assertFalse($this->getOptions(1)['http_errors']);
        $this->assertEquals('', $request->getBody());
        //var_dump($this->getRequst(0));
        //var_dump($this->getOptions(0));

        $this->expectException(\Porta\Billing\Exceptions\PortaConnectException::class);
        $response = $c->request('POST', '/Test/get_test', []);
    }

    public function testJsonResponse() {
        $data = ['key1' => 'val1', 'key2' => 'val2'];
        $dataJson = '{"key1": "val1", "key2": "val2"}';
        $this->assertEquals(
                ['key1' => 'val1', 'key2' => 'val2'],
                SessionClient::jsonResponse(new Response(200, [], $dataJson))
        );
        $this->assertEquals(
                [],
                SessionClient::jsonResponse(new Response(200, []))
        );
        $this->expectException(\Porta\Billing\Exceptions\PortaException::class);
        SessionClient::jsonResponse(new Response(200, [], 'NoJsonHere'));
    }

}
