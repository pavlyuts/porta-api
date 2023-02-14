<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

use PortaApi\Session\SessionStorageInterface;
use PortaApi\Exceptions\PortaESPFException;
use GuzzleHttp\RequestOptions as RO;
use GuzzleHttp\Psr7\Response;

/**
 * Erapper for ESPF API
 *
 */
class ESPF {

    use Traits\ClientSafeTrait;
    use Traits\SessionTrait;

    function __construct(array $config, SessionStorageInterface $storage = null) {
        $this->setupClient($config, Config::ESPF_BASE);
        $this->setupSession($config, $storage);
    }

    /**
     * GET request, params will be send as query string
     * 
     * @param string $endpoint - endpoint to call
     * @param array $params - associative array of params, may omit
     * @return associative array for returned JSON
     * 
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     *         
     */
    public function get(string $endpoint, array $params = []): array {
        $response = $this->requestSafe('GET', $endpoint, ([] == $params) ? [] : [RO::QUERY => $params]);
        return $this->jsonResponse($response);
    }

    /**
     * POST request, params will be sent as JSON body
     * 
     * @param string $endpoint - endpoint to call
     * @param array $params - associative array of params, may omit
     * @return associative array for returned JSON or empty array on empty 
     *         billing answer
     * 
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     */
    public function post(string $endpoint, array $params = []): array {
        $response = $this->requestSafe('POST', $endpoint, ([] == $params) ? [] : [RO::JSON => $params]);
        return $this->jsonResponse($response);
    }

    /**
     * PUT request, params mandatory and will be sent as JSON body
     * 
     * @param string $endpoint - endpoint to call
     * @param array $params - associative array of params, mandatory
     * @return associative array for returned JSON.
     * 
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     */
    public function put(string $endpoint, array $params): array {
        $response = $this->requestSafe('PUT', $endpoint, [RO::JSON => $params]);
        return $this->jsonResponse($response);
    }

    /**
     * DELETE request, no params
     * 
     * @param string $endpoint - endpoint to call
     * @return void
     * 
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     */
    public function delete(string $endpoint): void {
        $this->requestSafe('DELETE', $endpoint);
    }

    protected function isAuthError(Response $response): bool {
        return 401 == $response->getStatusCode();
    }

    protected function processPortaError(Response $response): void {
        throw new PortaESPFException("ESPF API error", $response->getStatusCode());
    }

}
