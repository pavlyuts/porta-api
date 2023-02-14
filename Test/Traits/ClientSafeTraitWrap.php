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
class ClientSafeTraitWrap {

    use \PortaApi\Traits\ClientSafeTrait;

    protected $relogins = 0;
    protected $sessionUp = true;

    public function __construct(array $options = [], $sessionUp = true) {
        $this->setupClient([C::HOST => 'testhost.dom', C::OPTIONS => $options], '/test');
        $this->sessionUp = $sessionUp;
    }

    protected function getAuthHeader() {
        return ['Authorization' => 'Bearer TokenStringHere'];
    }

    public function pubSendSafe(Request $request, array $options = []): Response {
        return $this->sendSafe($request, $options);
    }

    public function pubRequestSafe(string $method, $uri = '', array $options = []): Response {
        return $this->requestSafe($method, $uri, $options);
    }

    protected function relogin() {
        $this->relogins++;
    }

    public function getRelogins() {
        return $this->relogins;
    }

    protected function isAuthError(Response $response): bool {
        return $response->getStatusCode() == 401;
    }

    protected function isSessionUp(): bool {
        return $this->sessionUp;
    }

    protected function processPortaError(Response $response): void {
        throw new PortaException($response->getReasonPhrase(), $response->getStatusCode());
    }

}
