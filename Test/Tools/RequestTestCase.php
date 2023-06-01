<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * Class for testing HTTT requests
 */
class RequestTestCase extends \PHPUnit\Framework\TestCase {

    protected $container = [];

    protected function prepareRequests(array $answers) {
        $mock = new MockHandler($answers);
        $handlerStack = HandlerStack::create($mock);
        $this->container = [];
        $handlerStack->push(Middleware::history($this->container));

        return ['handler' => $handlerStack];
    }

    protected function getRequst($index): ?Request {
        return $this->container[$index]['request'] ?? null;
    }

    protected function getOptions($index): ?array {
        return $this->container[$index]['options'] ?? null;
    }

}
