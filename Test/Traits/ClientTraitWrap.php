<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Traits;

use PortaApi\Config as C;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PortaApi\Exceptions\PortaException;

/**
 * Description of GuzzleTraitWrap
 *
 */
class ClientTraitWrap {

    use \PortaApi\Traits\ClientTrait;

    public function __construct(array $options = []) {
        $this->setupClient([C::HOST => 'testhost.dom', C::OPTIONS => $options], '/test');
    }

    protected function getAuthHeader() {
        return ['Authorization' => 'Bearer TokenStringHere'];
    }

    public function pubSend(Request $request, array $options = []) {
        return $this->send($request, $options);
    }

    public function pubRequest(string $method, $uri = '', array $options = []): \GuzzleHttp\Psr7\Response {
        return $this->request($method, $uri, $options);
    }

    public function pubTestSetup() {
        $this->setupClient([C::HOST => '']);
    }

}
