<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing;

use Porta\Billing\Interfaces\ConfigInterface;
use Porta\Billing\Interfaces\SessionStorageInterface;
use Porta\Billing\Exceptions\PortaESPFException;
use GuzzleHttp\RequestOptions as RO;
use GuzzleHttp\Psr7\Response;

/**
 * Wrapper for ESPF API
 *
 * The class is intended to provide interface to Portaone ESPF API. It handles
 * authorisation, access token management and ESPF call handling.
 * It needs:
 * - {@see ConfigInterface} object as billing server host, account and call options configuration.
 * - {@see SessionStorageInterface} object to store session data between invocanions.
 *
 * The difference ESPF to API call is that ESPF returns HTTP 40x codes in a case
 * of request failure. These codes will throw {@see PortaESPFException}. Meaning
 * of each code depends of endpoint called.
 *
 * See 'API documentation' section on <https://docs.portaone.com>
 *
 * @api
 * @package Billing
 */
class ESPF extends Components\BillingBase {

    /**
     * @inherit
     * @package Billing
     */
    public function __construct(ConfigInterface $config, ?SessionStorageInterface $storage = null) {
        parent::__construct($config, $storage);
    }

    /**
     * GET ESPF request
     *
     * Params will be encoded and send as query string
     *
     * @param string $endpoint endpoint to call
     * @param array $params associative array of params, may omit
     * @return array associative array for returned JSON
     *
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     * @api
     */
    public function get(string $endpoint, array $params = []): array {
        $response = $this->request('GET', $endpoint, ([] == $params) ? [] : [RO::QUERY => $params]);
        return static::jsonResponse($response);
    }

    /**
     * POST ESPF request
     *
     * Params will be encoded and sent as JSON body
     *
     * @param string $endpoint endpoint to call
     * @param array $params associative array of params, may omit
     * @return array associative array for returned JSON or empty array on empty billing answer
     *
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     * @api
     */
    public function post(string $endpoint, array $params = []): array {
        $response = $this->request('POST', $endpoint, ([] == $params) ? [] : [RO::JSON => ([] == ($params ?? [])) ? new \stdClass() : $params]);
        return static::jsonResponse($response);
    }

    /**
     * PUT ESPF request
     *
     * Params are mandatory and will be encoded and sent as JSON body
     *
     * @param string $endpoint endpoint to call
     * @param array $params associative array of params, mandatory
     * @return array associative array for returned JSON.
     *
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     * @api
     */
    public function put(string $endpoint, array $params): array {
        $response = $this->request('PUT', $endpoint, [RO::JSON => ([] == ($params ?? [])) ? new \stdClass() : $params]);
        return static::jsonResponse($response);
    }

    /**
     * DELETE ESPF request
     *
     * DELETE has no params, only endpoint. It returns HTTP 200 on success
     * and 40x on failure, so the methid wil return nothing on success and throw
     * PortaESPFException on error.
     *
     * @param string $endpoint endpoint to call
     * @return void
     *
     * @throws PortaESPFException on ESPF api error, check error coode with API docs
     * @api
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
