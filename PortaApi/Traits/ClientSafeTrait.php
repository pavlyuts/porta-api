<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Traits;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Trait to use Guzzle client with arror handling and re-login
 */
trait ClientSafeTrait {

    use \PortaApi\Traits\ClientTrait;

    protected function requestSafe(string $method, $uri = '', array $options = []): Response {
        if ($this->isSessionUp()) {
            $response = $this->request($method, $uri, $options);
            if (200 == $response->getStatusCode()) {
                return $response;
            }
            if (!$this->isAuthError($response)) {
                $this->processPortaError($response);
            }
        }
        $this->relogin();
        $response = $this->request($method, $uri, $options);
        if (200 != $response->getStatusCode()) {
            $this->processPortaError($response);
        }
        return $response;
    }

    protected function sendSafe(Request $request, array $options = []): Response {
        if ($this->isSessionUp()) {
            $response = $this->send($request, $options);
            if (200 == $response->getStatusCode()) {
                return $response;
            }
            if (!$this->isAuthError($response)) {
                $this->processPortaError($response);
            }
        }
        $this->relogin();
        $response = $this->send($request, $options);
        if (200 != $response->getStatusCode()) {
            $this->processPortaError($response);
        }
        return $response;
    }

    abstract protected function isSessionUp(): bool;

    abstract protected function relogin();

    abstract protected function processPortaError($response): void;

    abstract protected function isAuthError(Response $response): bool;
}
