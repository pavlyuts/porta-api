<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest;

use Porta\Billing\Config;
use Porta\Billing\Exceptions\PortaAuthException;

/**
 * Test class for PortaConfig
 *
 */
class PortaConfigTest extends \PHPUnit\Framework\TestCase {

    const HOST = 'server.dom';
    const ACCOUNT_PASS = ['login' => 'mylogin', 'password' => 'mypass'];
    const ACCOUNT_TOKEN = ['login' => 'mylogin', 'token' => 'mytoken'];
    const OPTIONS = ['option' => 'value'];

    public function testConstructDefaulats() {
        $c = new Config(self::HOST);
        $this->assertEquals('https://' . self::HOST, $c->getUrl());
        $this->assertFalse($c->hasAccount());
        $this->assertEquals([], $c->getOptions());
        $this->assertEquals(3600, $c->getSessionRefreshMargin());
    }

    public function testConstruct() {
        $c = new Config(self::HOST, self::ACCOUNT_PASS, self::OPTIONS, 7200);
        $this->assertEquals('https://' . self::HOST, $c->getUrl());
        $this->assertTrue($c->hasAccount());
        $this->assertEquals(self::ACCOUNT_PASS, $c->getAccount());
        $this->assertEquals(self::OPTIONS, $c->getOptions());
        $this->assertEquals(7200, $c->getSessionRefreshMargin());
    }

    public function accountData() {
        return [
            [[], false],
            [['login' => 'mylogin'], false],
            [['login' => 'mylogin', 'password' => 'mypass'], true],
            [['login' => 'mylogin', 'token' => 'mytoken'], true],
            [['password' => 'mypass'], false],
            [['token' => 'mytoken'], false],
            [['password' => 'mypass', 'token' => 'mytoken'], false],
        ];
    }

    /**
     * @dataProvider accountData
     */
    public function testSetAccount($account, $good) {
        $c = new Config(self::HOST);
        if (!$good) {
            $this->expectException(PortaAuthException::class);
        }
        $c->setAccount($account);
        $this->assertEquals($account, $c->getAccount());
    }

    public function testNullAccount() {
        $c = new Config(self::HOST);
        $c->setAccount();
        $this->expectException(PortaAuthException::class);
        $c->getAccount();
    }

    public function testOptions() {
        $c = new Config(self::HOST);
        $this->assertEquals([], $c->getOptions());
        $this->assertEquals($c, $c->setOptions(self::OPTIONS));
        $this->assertEquals(self::OPTIONS, $c->getOptions());
    }

}
