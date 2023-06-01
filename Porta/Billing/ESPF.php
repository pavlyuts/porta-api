<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing;

use Porta\Billing\Session\SessionStorageInterface;
use Porta\Billing\Exceptions\PortaESPFException;
use GuzzleHttp\RequestOptions as RO;
use GuzzleHttp\Psr7\Response;

/**
 * Wrapper for ESPF API
 *
 */
class ESPF extends Components\BillingBase {

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
        $response = $this->request('GET', $endpoint, ([] == $params) ? [] : [RO::QUERY => $params]);
        return static::jsonResponse($response);
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
        $response = $this->request('POST', $endpoint, ([] == $params) ? [] : [RO::JSON => ([] == ($params ?? [])) ? new \stdClass() : $params]);
        return static::jsonResponse($response);
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
        $response = $this->request('PUT', $endpoint, [RO::JSON => ([] == ($params ?? [])) ? new \stdClass() : $params]);
        return static::jsonResponse($response);
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
        $this->request('DELETE', $endpoint);
    }

    protected function getPathBase(): string {
        return $this->config->getEspfPath();
    }

    protected function isAuthError(Response $response): bool {
        return 401 == $response->getStatusCode();
    }

    protected function processPortaError(Response $response): void {
        throw new PortaESPFException("ESPF API error ", $response->getStatusCode());
    }

}
