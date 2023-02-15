<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

use PortaApi\Config as C;
use PortaApi\Session\SessionStorageInterface;
use PortaApi\Exceptions\PortaException;
use PortaApi\Exceptions\PortaApiException;
use PortaApi\Exceptions\PortaAuthException;
use GuzzleHttp\Psr7\Response;

/**
 * Base class, provides authorisation, session management and http call capability
 *
 */
class Billing {

    use \PortaApi\Traits\ClientSafeTrait;
    use \PortaApi\Traits\SessionTrait;

    function __construct(array $config, SessionStorageInterface $storage = null) {
        $this->setupClient($config, C::API_BASE);
        $this->setupSession($config, $storage);
    }

    /**
     * Perform billing API call.
     * Reference to your billing system API docs, located 
     * at https://your-billing-sip-host/doc/api/ or API docs section of Portaone
     * docs site https://docs.portaone.com/. Mind your billing release version.
     * 
     * @param string $endpoint - API endpoint as per docs, exapmle: '/Customer/get_customer_info'
     * @param array $params - API requst data to put into "params" section
     * 
     * @return array Billing system answer, converted to associative array. 
     *               If billing retuns file, returns array of two keys: 
     *               'filename' => string, returned file name, 
     *               'stream' => PSR-7 stream object with file
     * 
     * @throws PortaException on general errors
     * @throws PortaAuthException on auth-related errors
     * @throws PortaApiException on API returned an error
     */
    public function call(string $endpoint, array $params = []): array {
        $response = $this->requestSafe('POST', $endpoint, [\GuzzleHttp\RequestOptions::JSON => [Config::PARAMS => $params]]);
        switch (static::detectContentType($response)) {
            case 'application/json':
                return static::jsonResponse($response);
            case 'application/pdf':
            case 'application/csv':
                return static::extractFile($response);
            default:
                throw new PortaException("Missed or unknown content-type '" . ($parsed[0][0] ?? 'null') . "'in the billing answer");
        }
    }

    protected static function detectContentType(Response $response): string {
        $parsed = \GuzzleHttp\Psr7\Header::parse($response->getHeader('content-type'));
        return $parsed[0][0] ?? 'unknown';
    }

    protected static function extractFile(Response $response) {
        $parsed = \GuzzleHttp\Psr7\Header::parse($response->getHeader('content-disposition'));
        if ((($parsed[0][0] ?? '') != 'attachment') || !isset($parsed[0]['filename'])) {
            throw new PortaException("Invalid file content-disposition header");
        }
        return [
            'filename' => $parsed[0]['filename'],
            'stream' => $response->getBody(),
        ];
    }

    protected static function isAuthError(Response $response): bool {
        if (500 != $response->getStatusCode()) {
            return false;
        }
        $faultCode = json_decode((string) $response->getBody(), true)['faultcode'] ?? 'none';
        return in_array($faultCode,
                [
                    'Server.Session.check_auth.auth_failed',
                    'Client.check_auth.envelope_missed',
                    'Client.Session.check_auth.failed_to_process_jwt'
                ]
        );
    }

    protected function processPortaError($response): void {
        throw PortaApiException::createFromResponse($response);
    }

}
