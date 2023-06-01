<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Components;

use Porta\Billing\Components\SessionData;
use PortaApiTest\Tools\PortaToken;

/**
 * Tests for SessionData
 *
 */
class SessionDataTest extends \PHPUnit\Framework\TestCase {

    public function testCreate() {
        $data = PortaToken::createLoginData();
        $d = new SessionData($data);
        $this->assertTrue($d->isSet());
        $this->assertEquals($data, $d->getData());
        $this->assertEquals($data[SessionData::ACCESS_TOKEN], $d->getAccessToken());
        $this->assertEquals($data[SessionData::REFRESH_TOKEN], $d->getRefreshToken());
        $this->assertEquals(['Authorization' => 'Bearer ' . $data[SessionData::ACCESS_TOKEN]], $d->getAuthHeader());
        $this->assertInstanceOf(\Porta\Billing\Components\PortaTokenDecoder::class, $d->getTokenDecoder());
        $this->assertEquals($data[SessionData::EXPIRES_AT], $d->getAccessTokenExpire()->format('Y-m-d H:i:s'));
        $d[SessionData::REFRESH_TOKEN] = 'test';
        $this->assertEquals($data[SessionData::REFRESH_TOKEN], $d->getRefreshToken());
        unset($d[SessionData::REFRESH_TOKEN]);
        $this->assertEquals($data[SessionData::REFRESH_TOKEN], $d->getRefreshToken());
        return $d;
    }

    /**
     *
     * @depends testCreate
     */
    public function testUpdate(SessionData $d) {
        $data = PortaToken::createRefreshData(7200);
        $d->updateData($data);
        $this->assertEquals($data[SessionData::ACCESS_TOKEN], $d->getAccessToken());
    }

    public function testEmpty() {
        $d = new SessionData();
        $this->assertFalse($d->isSet());
        $this->assertEquals([], $d->getData());
        $this->assertNull($d->getAccessToken());
        $this->assertNull($d->getRefreshToken());
        $this->assertEquals([], $d->getAuthHeader());
        $this->assertInstanceOf(\Porta\Billing\Components\PortaTokenDecoder::class, $decoder = $d->getTokenDecoder());
        $this->assertFalse($decoder->isSet());
        $this->assertNull($d->getAccessTokenExpire());
    }

}
