<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PortaApi\Billing;
use PortaApi\Config as C;
use PortaApi\Exceptions\PortaException;
use PortaApi\Exceptions\PortaApiException;
use PortaApi\Exceptions\PortaAuthException;
use PortaApiTest\Tools\PortaToken;
use PortaApi\AsyncOperation;

/**
 * Tests for billing class
 *
 */
class BillingTest extends \PHPUnit\Framework\TestCase {

    const CONFIG = [
        C::HOST => 'testhost.dom',
        C::ACCOUNT => [
            C::LOGIN => 'testUser',
            C::PASSWORD => 'testPass',
        ],
    ];

    public function testCall() {
        $sessionData = PortaToken::createLoginData(7200);
        $testJsonData = ['testKey' => 'testValue'];
        $mock = new MockHandler([
            new Response(200, [], json_encode($sessionData)),
            new Response(200, ['content-type' => 'application/json; charset=UTF-8'], json_encode($testJsonData)),
            new Response(200,
                    ['content-type' => 'application/pdf', 'content-disposition' => 'attachment; filename="testfile.pdf"'],
                    'TestFileBody'),
            new Response(200,
                    ['content-type' => 'application/csv', 'content-disposition' => 'attachment; filename="testfile.csv"'],
                    'TestFileBody'),
            new Response(404, [], ''),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $storage = new Tools\SessionPHPClassStorage();
        $b = new Billing(array_merge(self::CONFIG, [C::OPTIONS => ['handler' => $handlerStack]]), $storage);
        $this->assertTrue($b->isSessionUp());
        $this->assertEquals($sessionData, $storage->load());

        $r = $b->call('/NoMatter', $testJsonData);
        $this->assertEquals($testJsonData, $r);

        $r = $b->call('/NoMatter', $testJsonData);
        $this->assertEquals('testfile.pdf', $r['filename']);
        $this->assertEquals('TestFileBody', (string) $r['stream']);

        $r = $b->call('/NoMatter', $testJsonData);
        $this->assertEquals('testfile.csv', $r['filename']);
        $this->assertEquals('TestFileBody', (string) $r['stream']);

        $this->expectException(PortaException::class);
        $r = $b->call('/NoMatter', $testJsonData);
    }

    public function testDetectContentFail() {
        $mock = new MockHandler([
            new Response(200, ['content-type' => 'application/xls']),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $storage = new Tools\SessionPHPClassStorage(PortaToken::createLoginData(7200));
        $b = new Billing(array_merge(self::CONFIG, [C::OPTIONS => ['handler' => $handlerStack]]), $storage);
        $this->expectException(PortaException::class);
        $r = $b->call('/NoMatter');
    }

    public function testExtractFileFail() {
        $mock = new MockHandler([
            new Response(200,
                    ['content-type' => 'application/pdf', 'content-disposition' => 'attachment'],
                    'TestFileBody'),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $storage = new Tools\SessionPHPClassStorage(PortaToken::createLoginData(7200));
        $b = new Billing(array_merge(self::CONFIG, [C::OPTIONS => ['handler' => $handlerStack]]), $storage);
        $this->expectException(PortaException::class);
        $r = $b->call('/NoMatter');
    }

    public function testAPIException() {
        $mock = new MockHandler([
            new Response(500,
                    ['content-type' => 'application/json'],
                    '{"faultcode": "WrongRequest", "faultstring": "WrongRequestMessage"}'),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $storage = new Tools\SessionPHPClassStorage(PortaToken::createLoginData(7200));
        $b = new Billing(array_merge(self::CONFIG, [C::OPTIONS => ['handler' => $handlerStack]]), $storage);
        $this->expectException(PortaApiException::class);
        $r = $b->call('/NoMatter');
    }

    /**
     * @covers \PortaApi\Billing::callAsync
     */
    public function testAsyncCall() {
        $list = [
            'key1' => new AsyncOperation('/test1', []),
            'key2' => new AsyncOperation('/test2', ['paramKey2' => 'paramValue2']),
            'key3' => new AsyncOperation('/test3', ['paramKey3' => 'paramValue3']),
            'key4' => new AsyncOperation('/test4', ['paramKey4' => 'paramValue4']),
            'keyNull' => new Tools\AsyncOperationNull(),
            'key5' => new AsyncOperation('/test5', ['paramKey5' => 'paramValue5']),
            'key6' => new AsyncOperation('/test6', []),
            'key7' => new AsyncOperation('/test7', ['paramKey7' => 'paramValue7']),
        ];
        $mock = new MockHandler([
            new Response(200, ['content-type' => 'application/json'], '{"user_id":1}'),
            new Response(200, ['content-type' => 'application/json'], '{}'),
            new Response(200, ['content-type' => 'application/json'], '{"answerKey2":"answerData2"}'),
            new Response(200, ['content-type' => 'application/json'], '{"answerKey3":"answerData3"}'),
            new Response(200, ['content-type' => 'application/json'], '{"answerKey4":"answerData4"}'),
            new Response(500,
                    ['content-type' => 'application/json'],
                    '{"faultcode": "WrongRequest", "faultstring": "WrongRequestMessage"}'),
            new \GuzzleHttp\Exception\ConnectException("Connection fail", new \GuzzleHttp\Psr7\Request('GET', '/test')),
            new Response(200, ['content-type' => 'application/json'], '{"answerKey7":"answerData7"}'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $storage = new Tools\SessionPHPClassStorage(PortaToken::createLoginData(7200));
        $b = new Billing(array_merge(self::CONFIG, [C::OPTIONS => ['handler' => $handlerStack]]), $storage);

        $this->assertFalse($list['key1']->executed());

        $b->callAsync($list);

        $this->assertFalse($list['keyNull']->executed());

        $this->assertTrue($list['key1']->executed());
        $this->assertTrue($list['key1']->success());
        $this->assertEquals([], $list['key1']->getResponse());

        $this->assertTrue($list['key2']->success());
        $this->assertEquals(['answerKey2' => 'answerData2'], $list['key2']->getResponse());

        $this->assertTrue($list['key3']->success());
        $this->assertEquals(['answerKey3' => 'answerData3'], $list['key3']->getResponse());

        $this->assertTrue($list['key4']->success());
        $this->assertEquals(['answerKey4' => 'answerData4'], $list['key4']->getResponse());

        $this->assertFalse($list['key5']->success());
        $this->assertInstanceOf(PortaApiException::class, $list['key5']->getException());

        $this->assertFalse($list['key6']->success());
        $this->assertInstanceOf(\PortaApi\Exceptions\PortaConnectException::class, $list['key6']->getException());

        $this->assertTrue($list['key7']->success());
        $this->assertEquals(['answerKey7' => 'answerData7'], $list['key7']->getResponse());
    }

    public function testWrongAsyncClassTypeException() {
        $b = new Billing([C::HOST => 'host']);
        $this->expectException(PortaException::class);
        $b->callAsync([[], new \stdClass]);
    }

    public function testAsyncNoSessionException() {
        $b = new Billing([C::HOST => 'host']);
        $this->expectException(PortaAuthException::class);
        $b->callAsync([new AsyncOperation('/test')]);
    }

}
