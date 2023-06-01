<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Exceptions;

use Porta\Billing\Exceptions\PortaAuthException;

/**
 * Exceptions tests
 *
 */
class PortaAuthExceptionTest extends \PHPUnit\Framework\TestCase {

    public function testAuthException() {
        $this->expectException(PortaAuthException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("Billing API authentification error");
        throw new PortaAuthException();
    }

    public function testCreateWithAccount() {
        $r = PortaAuthException::createWithAccount([]);
        $this->assertInstanceOf(PortaAuthException::class, $r);
        $this->assertEquals("Login failed with login 'null', password 'null', token 'null'", $r->getMessage());

        $r = PortaAuthException::createWithAccount(['login' => 'loginString', 'password' => 'passwordString', 'token' => 'tokenString']);
        $this->assertInstanceOf(PortaAuthException::class, $r);
        $this->assertEquals("Login failed with login 'loginString', password 'passwordString', token 'tokenString'", $r->getMessage());
    }

}
