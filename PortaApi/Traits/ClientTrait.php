<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PortaApi\Exceptions\PortaException;
use PortaApi\Exceptions\PortaApiException;
use PortaApi\Config;

/**
 * Trait to use Guzzle client
 */
trait ClientTrait {

    protected $host;
    protected $options = [];
    protected $base = '';

    protected function setupClient(array $config, string $base = '') {
        $this->host = $config[Config::HOST] ?? '';
        if ($this->host == '') {
            throw new \PortaApi\Exceptions\PortaException("Host name is mandatory");
        }
        $this->options = $config['options'] ?? [];
        $this->base = $base;
    }

    protected function request(string $method, $uri = '', array $options = []): Response {
        try {
            return $this->getClient()->request($method, $this->base . $uri, $options);
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            throw new \PortaApi\Exceptions\PortaConnectException($ex->getMessage(), $ex->getCode());
        }
    }

    protected function requestAddBase(Request $request): Request {
        $uri = $request->getUri();
        return $request->withUri($uri->withPath($this->base . $uri->getPath()));
    }

    protected function send(Request $request, array $options = []): Response {
        try {
            return $this->getClient()->send($this->requestAddBase($request), $options);
        } catch (\GuzzleHttp\Exception\ConnectException $ex) {
            throw new \PortaApi\Exceptions\PortaConnectException($ex->getMessage(), $ex->getCode());
        }
    }

    protected function getClient(array $options = []): Client {
        $opt = array_merge(
                [
                    'base_uri' => 'https://' . $this->host,
                    RequestOptions::HTTP_ERRORS => false,
                ],
                $this->options,
                $options
        );
        $opt['headers'] = array_merge($opt['headers'] ?? [], $this->getAuthHeader());
        return $this->client = new Client($opt);
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
            throw new PortaException("Can't decode returned JSON");
        }
        return $result;
    }

    /**
     * Convert billing-supplied UTC time string to DateTime object with target 
     * timezone
     * 
     * @param string $billingTime - datetime string as billing returns
     * @param string $timezone - timezone string like 'Europe/London" or '+3000',
     *                         as defined at https://www.php.net/manual/en/datetimezone.construct.php
     * @return \DateTime
     */
    public static function timeToLocal(string $billingTime, string $timezone = UTC): \DateTime {
        return (new \DateTime($billingTime, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($timezone));
    }

    /**
     * Convert Datetime object to billing API string at UTC
     * 
     * @param \DateTime $time - Object to convert
     * @return string   Billing API-type datetime string, shifted to UTC
     */
    public static function timeToBilling(\DateTime $time): string {
        return $time->setTimezone(new \DateTimeZone('UTC'))->format(Config::DATETIME_FORMAT);
    }

    abstract protected function getAuthHeader(): array;
}
