<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest;

use PortaApi\Billing;
use PortaApi\Config as C;
use PortaApiTest\Tools\PortaToken;
use PortaApi\Exceptions\PortaException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

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
        $this->expectException(\PortaApi\Exceptions\PortaApiException::class);
        $r = $b->call('/NoMatter');
    }

}
