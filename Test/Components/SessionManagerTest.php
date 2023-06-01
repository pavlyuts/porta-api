<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Components;

use Porta\Billing\PortaConfig;
use Porta\Billing\Components\SessionClient;
use Porta\Billing\Components\SessionManager;
use Porta\Billing\Components\SessionData;
use Porta\Billing\Session\SessionStorageInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PortaApiTest\Tools\PortaToken;
use PortaApiTest\Tools\SessionPHPClassStorage;

/**
 * Test for SessonManager
 */
class SessionManagerTest extends \PortaApiTest\Tools\RequestTestCase {

    const ACCOUNT = ['login' => 'username', 'password' => 'password'];

    public function testLoadAndLogout() {

        $conf = (new PortaConfig('host.dom'))
                ->setOptions(
                $this->prepareRequests([
                    new Response(200, [], '{"success": 1}'),
                        ]
                )
        );
        $sessionData = new SessionData(PortaToken::createLoginData(7200));
        $storage = new SessionPHPClassStorage($sessionData->getData());
        $client = new SessionClient($conf);

        $s = new SessionManager($conf, $client, $storage);

        $this->assertTrue($s->isSessionUp());
        $this->assertEquals(['Authorization' => 'Bearer ' . $sessionData->getAccessToken()], $s->getAuthHeader());
        $this->assertEquals('userName', $s->getUsername());

        //Logout
        $s->logout();
        $this->assertFalse($s->isSessionUp());
        $this->assertEquals([], $s->getAuthHeader());
        $this->assertNull($s->getUsername());
        $request = $this->getRequst(0);
        $this->assertEquals('https://host.dom/rest/Session/logout', (string) $request->getUri());
        $this->assertEquals([], $s->getAuthHeader());
        $this->assertFalse($this->getOptions(0)['http_errors']);
        $this->assertEquals(['params' => ['access_token' => $sessionData->getAccessToken()]], json_decode($request->getBody(), true));

        //Repeated Logout do nothing
        $s->logout();
    }

    public function testEmpty() {
        $conf = new PortaConfig('host.dom');
        $storage = new SessionPHPClassStorage();
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);

        $this->assertFalse($s->isSessionUp());
        $this->assertFalse($s->checkSession());
    }

    public function testLogin() {
        $conf = (new PortaConfig('host.dom'))
                ->setOptions(
                $this->prepareRequests([
                    new Response(200, [], json_encode(PortaToken::createLoginData(7600))),
                        ]
                )
        );
        $storage = new SessionPHPClassStorage();
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);

        $s->login(self::ACCOUNT);
        $this->assertTrue($s->isSessionUp());
        $request = $this->getRequst(0);
        $this->assertEquals('https://host.dom/rest/Session/login', (string) $request->getUri());
        $this->assertEquals([], $request->getHeader('Authorization'));
        $this->assertFalse($this->getOptions(0)['http_errors']);
        $this->assertEquals(['params' => self::ACCOUNT], json_decode($request->getBody(), true));

        //Test exception by lock
        $conf = new PortaConfig('host.dom');
        $storage = new SessionPHPClassStorage([], false);
        $s = new SessionManager($conf, $client, $storage);
        $this->expectException(\Porta\Billing\Exceptions\PortaException::class);
        $s->login(self::ACCOUNT);
    }

    public function testAuthFailed() {
        $conf = (new PortaConfig('host.dom'))
                ->setOptions(
                $this->prepareRequests([
                    new Response(500, [], '{"faultcode": "Server.Session.auth_failed",'
                            . '"faultstring": "The login or password is incorrect. '
                            . 'Please note: your password is case sensitive."}'),
                        ]
                )
        );
        $storage = new SessionPHPClassStorage();
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->expectException(\Porta\Billing\Exceptions\PortaAuthException::class);
        $s->login(self::ACCOUNT);
    }

    public function testLoginOtherFailed() {
        $conf = (new PortaConfig('host.dom'))
                ->setOptions(
                $this->prepareRequests([
                    new Response(501),
                        ]
                )
        );
        $storage = new SessionPHPClassStorage();
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->expectException(\Porta\Billing\Exceptions\PortaException::class);
        $s->login(self::ACCOUNT);
    }

    public function testLogoutConnectException() {
        $conf = (new PortaConfig('host.dom',))
                ->setOptions(
                $this->prepareRequests([
                    new \GuzzleHttp\Exception\ConnectException("Connect problems", new \GuzzleHttp\Psr7\Request('GET', '')),
                        ]
                )
        );
        $sessionData = new SessionData(PortaToken::createLoginData(3600));
        $storage = new SessionPHPClassStorage($sessionData->getData());
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->expectException(\Porta\Billing\Exceptions\PortaConnectException::class);
        $s->logout();
    }

    public function testTokenRefreshByLock() {
        $conf = new PortaConfig('host.dom');
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(100), false);
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertTrue($s->isSessionUp());
    }

    public function testTokenRefreshSuccess() {
        //Nothing happen if token expire time not within margin
        $conf = (new PortaConfig('host.dom', null, [], 100));
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(101));
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertTrue($s->isSessionUp());

        // Then should refresh it it is within margin
        $updateData = new SessionData(PortaToken::createLoginData(7600));
        $conf->setOptions(
                $this->prepareRequests([
                    new Response(200, [], json_encode($updateData->getData())),
                        ]
                )
        );
        $sessionData = new SessionData(PortaToken::createLoginData(99));
        $storage = new SessionPHPClassStorage($sessionData->getData());
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);

        $request = $this->getRequst(0);
        $this->assertEquals('https://host.dom/rest/Session/refresh_access_token', (string) $request->getUri());
        $this->assertFalse($this->getOptions(0)['http_errors']);
        $this->assertEquals(
                ['params' => [SessionData::REFRESH_TOKEN => $sessionData->getRefreshToken()]],
                json_decode($request->getBody(), true));
        $this->assertTrue($s->isSessionUp());
        $this->assertEquals(['Authorization' => 'Bearer ' . $updateData->getAccessToken()], $s->getAuthHeader());
    }

    public function testTockenRefreshFailed() {
        $conf = new PortaConfig('host.dom');
        $conf->setOptions(
                $this->prepareRequests([
                    new Response(500),
                        ]
                )
        );
        $sessionData = new SessionData(PortaToken::createLoginData(3599));
        $storage = new SessionPHPClassStorage($sessionData->getData());
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertFalse($s->isSessionUp());
    }

    public function testTockenRefreshFailedRelogin() {
        $conf = new PortaConfig('host.dom', self::ACCOUNT);
        $conf->setOptions(
                $this->prepareRequests([
                    new Response(500),
                    new Response(200, [], json_encode(PortaToken::createLoginData(7600))),
                        ]
                )
        );
        $sessionData = new SessionData(PortaToken::createLoginData(3599));
        $storage = new SessionPHPClassStorage($sessionData->getData());
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertTrue($s->isSessionUp());
    }

    public function testTikenExpiredNoAccount() {
        $conf = new PortaConfig('host.dom');
        $sessionData = new SessionData(PortaToken::createLoginData(-1));
        $storage = new SessionPHPClassStorage($sessionData->getData());
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertFalse($s->isSessionUp());
        // Relogin with no account throws exception
        $this->expectException(\Porta\Billing\Exceptions\PortaAuthException::class);
        $s->relogin();
    }

    public function testTikenExpiredNoAccountLoadOnWaitForLock() {
        $conf = new PortaConfig('host.dom');
        $sessionData = new SessionData(PortaToken::createLoginData(7200));
        $storage = new SessionPHPClassStorage($sessionData->getData(), false);
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertTrue($s->isSessionUp());
        // Relogin with no account throws exception
        $conf->setAccount(self::ACCOUNT);
        $s->relogin();
        $this->assertTrue($s->isSessionUp());
    }

    public function testChecksession() {
        $conf = (new PortaConfig('host.dom'))->setOptions(
                $this->prepareRequests([
                    new Response(200, [], '{"user_id": 0}'),
                    new Response(200, [], '{"user_id": 10}'),
                    new Response(200, [], '{}'),
                    new Response(500, [], '{"faultcode": "Server.failed", "faultstring": "Somenting goes wrong"}'),
                        ]
                )
        );
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(7200));
        $client = new SessionClient($conf);
        $s = new SessionManager($conf, $client, $storage);
        $this->assertFalse($s->checkSession());
        $this->assertTrue($s->checkSession());
        $this->assertFalse($s->checkSession());
        $this->expectException(\Porta\Billing\Exceptions\PortaApiException::class);
        $s->checkSession();
    }

}
