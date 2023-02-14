<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

use PortaApiTest\Tools\PortaToken;

/**
 * tests for PortaTokenTest
 *
 */
class PortaTokenTest extends \PHPUnit\Framework\TestCase {

    const TEST_DATETIME = '2023-02-07 10:20:30';
    const TEST_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJleHAiOjE2NzU3NjUyMz'
            . 'AsImlfdXNlciI6NSwiaV9lbnYiOjEsImxvZ2luIjoidXNlck5hbWUiLCJzY29wZXMiOi'
            . 'Jlc3BmLmFwaTp3cml0ZSBlc3BmLmhhbmRsZXJzOndyaXRlIiwiaWF0IjoxNjc1NTkyND'
            . 'MwLCJqdGkiOiIyY2ZjNjJjMjI1Mjk1NDcyNTY5MTM1MDJiMjcwY2MzYyIsInJlYWxtIj'
            . 'oiYWRtaW4iLCJhdWQiOlsicG9ydGFiaWxsaW5nLWFwaSIsInBvcnRhc2lwLWFwaSIsIm'
            . 'VzcGYtYXBpIl0sImlzX3N1cGVyX3VzZXIiOjB9.d_Bzu_NmpMWO-laANMDDxLT04F498'
            . 'WjufNuXwxw--5fA6vcvio5qPA3NA4UulpDiizc6OzcAuvoYh8vPucn1YR0TYxKTXKHnD'
            . '2fwkxuoKXZ36bEFb0BO6NM6GaPDjgoUuuF9E6E4x3C75Cw72c9rnp8Dc-5F_OkoYlVpI'
            . 'f0T-Y4nwxkSIEFbqtG5LDwgSyxir3dSCU1yUKKCo1FtNqhxQ2ycYIeTqvFlmCSsG8Cmm'
            . '1UFGadQx8ajEhnWN5ayoCO8nJJGZv-Tu6QOEGo4MG-HAHIcw0zrjM-0Bc52vjM4LjWli'
            . 'TMMexYIA61wxbXHmwLDY-vXFXYGMlgzI5dpQ7ZIZg';

    public function testCreateJWT() {
        $this->assertEquals(
                self::TEST_TOKEN,
                PortaToken::createJWT(new \DateTime(self::TEST_DATETIME, new \DateTimeZone('UTC')))
        );
    }

    /**
     * @dataProvider timeVariants
     */
    public function testCreateLoginData(int $dt) {
        $timestamp = time() + $dt;
        $t = (new \DateTime('now', new \DateTimeZone('UTC')))->setTimestamp($timestamp);
        $session = PortaToken::createLoginData($dt);
        $this->assertIsArray($session);
        $token = self::decodeToken($session['access_token']);
        $this->assertEquals($t, new \DateTime($session['expires_at'], new \DateTimeZone('UTC')));
        $this->assertEquals($timestamp, $token['exp']);
        $this->assertEquals($timestamp - 172800, $token['iat']);
    }

    public function testCreateRefreshData() {
        $session = PortaToken::createRefreshData();
        $this->assertArrayNotHasKey('session_id', $session);
        $this->assertArrayHasKey('refresh_token', $session);
        $this->assertArrayHasKey('expires_at', $session);
        $this->assertArrayHasKey('access_token', $session);
    }

    protected static function decodeToken(string $token) {
        $parts = explode('.', $token);
        $data = json_decode(base64_decode($parts[1]), true);
        return $data;
    }

    public function timeVariants() {
        return [[-100], [0], [100]];
    }

}
