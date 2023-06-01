<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Components;

use PortaApi\Components\BillingBase;
use PortaApi\PortaConfig;
use PortaApiTest\Tools\SessionPHPClassStorage;
use PortaApiTest\Tools\PortaToken;
use GuzzleHttp\Psr7\Response;

/**
 * Tests for BillingBase
 */
class BillingBaseTest extends \PortaApiTest\Tools\RequestTestCase {

    const ACCOUNT = ['login' => 'username', 'password' => 'password'];

    public function testSessionForwarded() {
        $config = (new PortaConfig('host.dom', self::ACCOUNT))
                ->setOptions($this->prepareRequests(
                        [
                            new Response(200),
                            new Response(200, [], json_encode(PortaToken::createLoginData(7200))),
                        ]
        ));
        $storage = new SessionPHPClassStorage(PortaToken::createLoginData(7200));
        /** @var BillingBase $b */
        $b = $this->getMockForAbstractClass(BillingBase::class, [$config, $storage]);
        $this->assertTrue($b->isSessionUp());
        $this->assertEquals('userName', $b->getUsername());
        $b->logout();
        $this->assertFalse($b->isSessionUp());
        $b->login(self::ACCOUNT);
        $this->assertTrue($b->isSessionUp());
    }

}
