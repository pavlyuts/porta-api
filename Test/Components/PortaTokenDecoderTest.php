<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Components;

use Porta\Billing\Components\PortaTokenDecoder;

/**
 * Test for PortaToken
 *
 */
class PortaTokenDecoderTest extends \PHPUnit\Framework\TestCase {

    const TEST_DATETIME = '2023-02-07 10:20:30';
    const TEST_TOKEN = <<< EOD
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJleHAiOjE2NzU3NjUyMz
AsImlfdXNlciI6NSwiaV9lbnYiOjEsImxvZ2luIjoidXNlck5hbWUiLCJzY29wZXMiOi
Jlc3BmLmFwaTp3cml0ZSBlc3BmLmhhbmRsZXJzOndyaXRlIiwiaWF0IjoxNjc1NTkyND
MwLCJqdGkiOiIyY2ZjNjJjMjI1Mjk1NDcyNTY5MTM1MDJiMjcwY2MzYyIsInJlYWxtIj
oiYWRtaW4iLCJhdWQiOlsicG9ydGFiaWxsaW5nLWFwaSIsInBvcnRhc2lwLWFwaSIsIm
VzcGYtYXBpIl0sImlzX3N1cGVyX3VzZXIiOjB9.d_Bzu_NmpMWO-laANMDDxLT04F498
WjufNuXwxw--5fA6vcvio5qPA3NA4UulpDiizc6OzcAuvoYh8vPucn1YR0TYxKTXKHnD
2fwkxuoKXZ36bEFb0BO6NM6GaPDjgoUuuF9E6E4x3C75Cw72c9rnp8Dc-5F_OkoYlVpI
f0T-Y4nwxkSIEFbqtG5LDwgSyxir3dSCU1yUKKCo1FtNqhxQ2ycYIeTqvFlmCSsG8Cmm
1UFGadQx8ajEhnWN5ayoCO8nJJGZv-Tu6QOEGo4MG-HAHIcw0zrjM-0Bc52vjM4LjWli
TMMexYIA61wxbXHmwLDY-vXFXYGMlgzI5dpQ7ZIZg
EOD;
    const TEST_TOKEN_DATA = [
        "exp" => 1675765230,
        "i_user" => 5,
        "i_env" => 1,
        "login" => "userName",
        "scopes" => "espf.api:write espf.handlers:write",
        "iat" => 1675592430,
        "jti" => "2cfc62c22529547256913502b270cc3c",
        "realm" => "admin",
        "aud" => [
            "portabilling-api",
            "portasip-api",
            "espf-api"
        ],
        "is_super_user" => 0
    ];

    public function testCreate() {
        $t = new PortaTokenDecoder(self::TEST_TOKEN);
        $this->assertTrue($t->isSet());
        $this->assertEquals(self::TEST_TOKEN_DATA['exp'], $t->getExpire()->getTimestamp());
        $this->assertEquals(self::TEST_TOKEN_DATA['iat'], $t->getIssued()->getTimestamp());
        $this->assertEquals(self::TEST_TOKEN_DATA['login'], $t->getLogin());
        $this->assertEquals(self::TEST_TOKEN_DATA['jti'], $t['jti']);
        $t['test'] = 'test';
        $this->assertNull($t['test']);
        unset($t['test']);
        $t->setToken();
        $this->assertFalse($t->isSet());
    }

    public function testBrokenToken() {
        $t = new PortaTokenDecoder('NotAToken');
        $this->assertFalse($t->isSet());
        $this->assertNull($t->getExpire());
        $this->assertNull($t->getIssued());
        $this->assertNull($t->getLogin());
        $this->assertNull($t['jti']);
    }

}
