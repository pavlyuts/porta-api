<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Session;

use PortaApi\Config as C;
use PortaApi\Session\Session;
use PortaApi\Exceptions\PortaException;
use PortaApi\Exceptions\PortaAuthException;
use PortaApiTest\Tools\PortaToken;
use PortaApiTest\Tools\SessionPHPClassStorage;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Test class for session clsass
 *
 */
class SessionTest extends \PHPUnit\Framework\TestCase {

    const CONFIG_ACCOUNT = [
        C::HOST => 'testhost.dom',
        C::ACCOUNT => [
            C::LOGIN => 'testLogin',
            C::PASSWORD => 'testPass',
        //C::TOKEN => 'testToken',
        ],
    ];
    const CONFIG_MIN = [C::HOST => 'testhost.dom'];

    public function testNoAccountStorageEmpty() {
        $storage = new SessionPHPClassStorage();
        $s = new Session(self::CONFIG_MIN, $storage);
        $this->assertFalse($s->isSessionUp());
    }

    public function testHasAccountStorageEmpty() {
        $config = self::CONFIG_ACCOUNT;
        $loginAnswer = PortaToken::createLoginData(5000);
        $mock = new MockHandler([
            new Response(200, [], json_encode($loginAnswer)),
            new Response(200, [], '{"success": 1}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $container = [];
        $handlerStack->push(Middleware::history($container));
        $storage = new SessionPHPClassStorage();
        $config[C::OPTIONS]['handler'] = $handlerStack;

        $s = new Session($config, $storage);
        $request = $container[0]['request'];
        $this->assertEquals('/rest/Session/login', $request->getUri()->getPath());
        $this->assertEquals(['params' => $config[C::ACCOUNT]], json_decode($request->getBody(), true));
        $this->assertEquals($loginAnswer, $storage->load());

        unset($s);
        $s = new Session($config, $storage);
        var_dump(count($container));
        $this->assertTrue($s->isSessionUp());
        $s->logout();
        $this->assertFalse($s->isSessionUp());
        $request = $container[1]['request'];
        $this->assertEquals('/rest/Session/logout', $request->getUri()->getPath());
        $this->assertEquals([], json_decode($request->getBody(), true));
        $this->assertNull($storage->load());
    }

    public function testFromEmptyViaLogin() {
        $config = self::CONFIG_MIN;
        $loginAnswer = PortaToken::createLoginData(5000);
        $mock = new MockHandler([
            new Response(200, [], json_encode($loginAnswer)),
            new Response(500, [], ''),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $container = [];
        $handlerStack->push(Middleware::history($container));
        $storage = new SessionPHPClassStorage();
        $config[C::OPTIONS]['handler'] = $handlerStack;

        $s = new Session($config, $storage);
        $this->assertFalse($s->isSessionUp());

        $s->login(self::CONFIG_ACCOUNT[C::ACCOUNT]);
        $request = $container[0]['request'];
        $this->assertEquals('/rest/Session/login', $request->getUri()->getPath());
        $this->assertEquals(['params' => self::CONFIG_ACCOUNT[C::ACCOUNT]], json_decode($request->getBody(), true));
        $this->assertEquals($loginAnswer, $storage->load());
        $this->expectException(PortaException::class);
        $s->logout();
    }

    /**
     * @dataProvider accountVariants
     */
    public function testBadAccountLogin(array $account) {
        $s = new Session([C::HOST => 'testhost.dom', C::ACCOUNT => $account]);
        $this->expectException(PortaException::class);
        $s->login([]);
    }

    public function accountVariants() {
        return [
            [[]],
            [[C::LOGIN => 'testLogin']],
            [[C::PASSWORD => 'testPass']],
            [[C::TOKEN => 'testToken']],
            [[C::PASSWORD => 'testPass', C::TOKEN => 'testToken']],
        ];
    }

    public function testRefreshToken() {
        $config = self::CONFIG_ACCOUNT;
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(500));
        $refreshAnswer = PortaToken::createLoginData(7200);
        $reloginAnswer = PortaToken::createLoginData(9600);
        $mock = new MockHandler([
            new Response(200, [], json_encode($refreshAnswer)),
            new Response(500, [], json_encode([])),
            new Response(200, [], json_encode($reloginAnswer)),
            new Response(501, [], json_encode([])),
            new Response(200, [], json_encode($reloginAnswer)),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $container = [];
        $handlerStack->push(Middleware::history($container));
        $config[C::OPTIONS]['handler'] = $handlerStack;

        //Refresh without relogin
        $s = new Session($config, $storage);
        $this->assertTrue($s->isSessionUp());
        $this->assertEquals($refreshAnswer, $storage->load());

        //Refresh fail and relogin
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(500));
        $s = new Session($config, $storage);
        $this->assertTrue($s->isSessionUp());
        $this->assertEquals($reloginAnswer, $storage->load());

        //Refresh error, relogin 
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(500));
        $s = new Session($config, $storage);
        $this->assertTrue($s->isSessionUp());
        $this->assertEquals($reloginAnswer, $storage->load());
    }

    public function testLoginFailed() {
        $config = self::CONFIG_ACCOUNT;
        $mock = new MockHandler([
            new Response(500, [], json_encode(["faultcode" => "Server.Session.auth_failed"])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $config[C::OPTIONS]['handler'] = $handlerStack;
        $this->expectException(PortaAuthException::class);
        $s = new Session($config);
    }

    public function testLoginAPIerror() {
        $config = self::CONFIG_ACCOUNT;
        $mock = new MockHandler([
            new Response(501, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $config[C::OPTIONS]['handler'] = $handlerStack;
        $this->expectException(PortaException::class);
        $s = new Session($config);
    }

    public function testLockedStorage() {
        $config = self::CONFIG_ACCOUNT;
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(500), false);
        $s = new Session($config, $storage);
        $this->assertTrue($s->isSessionUp());
        $s->relogin();
        $this->assertTrue($s->isSessionUp());
        $this->expectException(PortaException::class);
        $s->login(self::CONFIG_ACCOUNT[C::ACCOUNT]);
    }

    public function testReloginNoAccount() {
        $config = self::CONFIG_MIN;
        $s = new Session($config);
        $this->expectException(PortaException::class);
        $s->relogin();
    }

    public function testCheckSession() {
        //No active session
        $s = new Session(self::CONFIG_MIN);
        $this->assertFalse($s->checkSession());

        //With active session
        $config = self::CONFIG_ACCOUNT;
        $sessionData = PortaToken::createLoginData(7200);
        $storage = new SessionPHPClassStorage($sessionData);
        $mock = new MockHandler([
            new Response(200, [], '{"user_id": 10}'),
            new Response(200, [], '{"user_id": 0}'),
            new Response(500, []),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $container = [];
        $handlerStack->push(Middleware::history($container));
        $config[C::OPTIONS]['handler'] = $handlerStack;

        //Test good
        $s = new Session($config, $storage);
        $this->assertTrue($s->checkSession());
        $request = $container[0]['request'];
        $this->assertEquals(['params' => ['access_token' => $sessionData['access_token']]],
                json_decode((string) $request->getBody(), true));
        $this->assertEquals('/rest/Session/ping', $request->getUri()->getPath());
        $this->assertEquals('POST', $request->getMethod());

        //Test not good session
        $this->assertFalse($s->checkSession());

        $this->expectException(PortaException::class);
        $s->checkSession();
    }

    public function testGetUsername() {
        $s = new Session(self::CONFIG_MIN, new SessionPHPClassStorage(PortaToken::createLoginData(7200)));
        $this->assertEquals('userName', $s->getUsername());

        $s = new Session(self::CONFIG_MIN);
        $this->assertNull($s->getUsername());
    }
    
    public function testLogoutFromEmpty() {
        $s = new Session(self::CONFIG_MIN);
        $this->assertNull($s->logout());
    }

}
