<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Components;

use PortaApi\PortaConfigInterface;
use PortaApi\Components\SessionManager;
use PortaApi\Session\SessionStorageInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

/**
 * Adopted http client
 *
 * @internal
 */
class SessionClient extends \GuzzleHttp\Client {

    /** Billing API datetime format */
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** Key for main API method params array */
    const PARAMS = 'params';

    protected PortaConfigInterface $config;
    protected SessionManager $session;

    public function __construct(PortaConfigInterface $config) {
        $this->config = $config;
        parent::__construct(array_merge(
                        [
                            'base_uri' => $this->config->getUrl(),
                            RequestOptions::HTTP_ERRORS => false,
                        ],
                        $this->config->getOptions()
        ));
    }

    public function setSesson(SessionManager $session) {
        $this->session = $session;
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface {
        //$options['headers'] = array_merge($options['headers'] ?? [], $this->session->getAuthHeader());
        try {
            return parent::request($method, $uri, $options);
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            throw new \PortaApi\Exceptions\PortaConnectException($ex->getMessage(), $ex->getCode());
        }
    }

    public function requestAsync(string $method, $uri = '', array $options = []): \GuzzleHttp\Promise\PromiseInterface {
        $options['headers'] = array_merge($options['headers'] ?? [], $this->session->getAuthHeader());
        return parent::requestAsync($method, $uri, $options);
    }

    /**
     * Extracts body from response object and decode it to array
     *      *
     * @param Response $response
     * @return array
     * @throws PortaApiException in a case of can't decode
     */
    public static function jsonResponse(Response $response): array {
        if (0 == $response->getBody()->getSize()) {
            return [];
        }
        $result = json_decode($response->getBody(), true);
        if (is_null($result)) {
            throw new \PortaApi\Exceptions\PortaException("Can't decode returned JSON");
        }
        return $result;
    }

}
