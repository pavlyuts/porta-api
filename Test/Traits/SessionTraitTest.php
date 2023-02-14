<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Traits;

use PortaApi\Config as C;
use PortaApiTest\Tools\PortaToken;
use PortaApi\Exceptions\PortaException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

/**
 * Tests for SessionTrait
 *
 */
class SessionTraitTest extends \PHPUnit\Framework\TestCase {

    const CONFIG = [
        C::HOST => 'testhost.dom',
        C::ACCOUNT => [
            C::LOGIN => 'testUser',
            C::PASSWORD => 'testPass',
        ],
    ];

    public function testMostMethods() {
        $sessionData = PortaToken::createLoginData(7200);
        $sessionData2 = PortaToken::createLoginData(10000);

        $mock = new MockHandler([
            new Response(200, []),
            new Response(200, [], json_encode($sessionData)),
            new Response(200, [], json_encode($sessionData2)),
            new Response(200, []),
            new Response(200),
            new Response(404),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $storage = new \PortaApiTest\Tools\SessionPHPClassStorage($sessionData);
        $s = new SessionTraitWrap(array_merge(self::CONFIG, [C::OPTIONS => ['handler' => $handlerStack]]), $storage);
        $this->assertEquals('userName', $s->getUsername());
        $this->assertTrue($s->isSessionUp());
        $this->assertEquals(['Authorization' => 'Bearer ' . $sessionData['access_token']], $s->pubGetAuthHeader());

        $s->logout();
        $this->assertNull($s->getUsername());
        $this->assertNull($storage->load());
        $this->assertFalse($s->isSessionUp());

        $s->login(self::CONFIG[C::ACCOUNT]);
        $this->assertEquals('userName', $s->getUsername());
        $this->assertTrue($s->isSessionUp());
        $this->assertEquals($sessionData, $storage->load());

        $s->pubRelogin();
        $this->assertEquals($sessionData2, $storage->load());
    }

}
