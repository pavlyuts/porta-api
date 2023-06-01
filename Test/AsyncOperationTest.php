<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest;

use PortaApi\AsyncOperation;
use PortaApiTest\Tools\AsyncOperationNull;

/**
 * Tests for AsyncOperation
 *
 */
class AsyncOperationTest extends \PHPUnit\Framework\TestCase {

    public function testAsyncOperaton() {
        $ao = new AsyncOperation('/test', ['paramsKey' => 'paramsValue']);
        $this->assertFalse($ao->executed());
        $this->assertFalse($ao->success());
        $this->assertEquals('/test', $ao->getCallEndpoint());
        $this->assertEquals(['paramsKey' => 'paramsValue'], $ao->getCallParams());
        $ao->processResponse(['responseKey' => 'responseValue']);
        $this->assertTrue($ao->executed());
        $this->assertTrue($ao->success());
        $this->assertEquals(['responseKey' => 'responseValue'], $ao->getResponse());

        $ao = new AsyncOperation('/test', ['paramsKey' => 'paramsValue']);
        $ao->processException(new \PortaApi\Exceptions\PortaException('TestMessage'));
        $this->assertTrue($ao->executed());
        $this->assertFalse($ao->success());
        $this->assertInstanceOf(\PortaApi\Exceptions\PortaException::class, $ao->getException());
    }

    public function testAsyncOperationNull() {
        $ao = new AsyncOperationNull();
        $this->assertNull($ao->getCallEndpoint());
    }

}
