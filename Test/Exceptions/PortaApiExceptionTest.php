<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Exceptions;

use Porta\Billing\Exceptions\PortaException;
use Porta\Billing\Exceptions\PortaApiException;
use Porta\Billing\Exceptions\PortaAuthException;
use GuzzleHttp\Psr7\Response;

/**
 * Exceptions tests
 *
 */
class PortaApiExceptionTest extends \PHPUnit\Framework\TestCase {

    public function testApiException() {
        try {
            throw new PortaApiException('Test Message', "777");
        } catch (PortaApiException $ex) {
            $this->assertEquals('777', $ex->getPortaCode());
            $this->assertEquals('Test Message', $ex->getMessage());
            $this->assertStringContainsString("Porta\Billing\Exceptions\PortaApiException: Test Message, error code '777' in", (string) $ex);
        }
    }

    public function testCreateFromResponse() {
        $r = new Response(500, [], '{"faultcode": "faultCodeString", "faultstring": "faultMessageString"}');
        $r = PortaApiException::createFromResponse($r);
        $this->assertInstanceOf(PortaApiException::class, $r);
        $this->assertEquals("faultCodeString", $r->getPortaCode());
        $this->assertEquals("faultMessageString", $r->getMessage());

        $r = (new Response())->withStatus(403, "TestReasonString");
        $r = PortaApiException::createFromResponse($r);
        $this->assertInstanceOf(PortaException::class, $r);
        $this->assertEquals(403, $r->getCode());
        $this->assertEquals("Request returned error 403, 'TestReasonString'", $r->getMessage());
    }

}
