<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PortaApi\Config as C;
use PortaApi\Exceptions\PortaException;
use PortaApi\Exceptions\PortaApiException;
use PortaApi\Exceptions\PortaAuthException;
use PortaApi\Exceptions\PortaConnectException;
use PortaApi\Session\SessionStorageInterface;
use PortaApi\Traits\SessionTrait;
use PortaApi\Traits\ClientSafeTrait;

/**
 * Base class, provides authorisation, session management and http call capability
 *
 */
class Billing {

    use ClientSafeTrait;
    use SessionTrait;

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
        $response = $this->requestSafe('POST', $endpoint, self::prepareParamsJson($params));
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

    /**
     * Perform bulk async billig call running multiple concurrent requests at once.
     * It will validate session to the billing before bulk call to be safe and throw
     * exception if session is not valid.
     * 
     * @param Traversable $operations array or any other traversable list of  of 
     *        objects, implementing BillingAsyncOperationInterface. Data for billing
     *        call wil be taken from the objects and each call result or exception
     *        will be put into the object
     * 
     * @param int $concurency - how much calls to run in parallel. Default is 20.
     * 
     * @throws PortaAuthException
     */
    public function callAsync(iterable $operations, int $concurency = 20) {
        $this->checkAsyncObjectList($operations);
        if (!$this->session->checkSession()) {
            throw new PortaAuthException("No active session to run async request");
        }

        $client = $this->getClient();
        $base = $this->base;
        $tasksIterator = function () use ($client, $operations, $base) {
            foreach ($operations as $index => $operation) {
                if (null === $operation->getCall()) {
                    continue;
                } else {
                    $func = function () use ($client, $base, $operations, $index) {
                        $callData = $operations[$index]->getCall();
                        return $promise = $client->postAsync($base . $callData[0] ?? 'null', Billing::prepareParamsJson($callData[1] ?? []))
                        ->then(
                        function (Response $response) use ($operations, $index) {
                            if (200 == $response->getStatusCode()) {
                                $operations[$index]->processResponse(Billing::jsonResponse($response));
                            } else {
                                $operations[$index]->processException(PortaApiException::createFromResponse($response));
                            }
                        },
                        function (\GuzzleHttp\Exception\ConnectException $reason) use ($operations, $index) {
                            $operations[$index]->processException(new PortaConnectException($reason->getMessage(), $reason->getCode()));
                        }
                        );
                    };
                    yield $func;
                }
            }
        };
        $pool = new Pool($client, $tasksIterator(), ['concurrency' => $concurency]);
        $promise = $pool->promise();
        $promise->wait();
    }

    /**
     * Same as call(), but if IPI returns an array with one element (basially it
     * has key like 'customer_list'), it will cut one level and return the list 
     * itself. If Billing returns more that one element on the top level, it returns 
     * the whole array. Sample: instead of ['customer_list' => [{customer data here}]]
     * it will return [{customer data here}] array directly.
     * 
     * @param string $endpoint - API endpoint as per docs, exapmle: '/Customer/get_customer_info'
     * @param array $params - API requst data to put into "params" section
     * 
     * @return array Billing system answer, converted to associative array, cut 
     *               one level if the returned array has only one key on the top level.
     *               If billing retuns file, returns array of two keys: 
     *               'filename' => string, returned file name,
     *               'mime' => string, content-type, 
     *               'stream' => PSR-7 stream object with file
     * 
     * @throws PortaException on general errors
     * @throws PortaAuthException on auth-related errors
     * @throws PortaApiException on API returned an error
     */
    public function callList(string $endpoint, array $params = []) {
        $answer = $this->call($endpoint, $params);
        $keys = array_keys($answer);
        return (count($keys) > 1) ? $answer : $answer[$keys[0]];
    }

    protected static function detectContentType(Response $response): string {
        $parsed = Header::parse($response->getHeader('content-type'));
        return $parsed[0][0] ?? 'unknown';
    }

    protected static function extractFile(Response $response) {
        $parsed = Header::parse($response->getHeader('content-disposition'));
        if ((($parsed[0][0] ?? '') != 'attachment') || !isset($parsed[0]['filename'])) {
            throw new PortaException("Invalid file content-disposition header");
        }
        return [
            'filename' => $parsed[0]['filename'],
            'mime' => static::detectContentType($response),
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
                    'Client.Session.check_auth.failed_to_process_jwt',
                    'Client.Session.check_auth.failed_to_process_access_token'
                ]
        );
    }

    protected function processPortaError($response): void {
        throw PortaApiException::createFromResponse($response);
    }

    protected static function prepareParamsJson(array $params) {
        return [
            RequestOptions::JSON =>
            [
                Config::PARAMS => ([] == ($params ?? [])) ? new \stdClass() : $params,
            ]
        ];
    }

    protected function checkAsyncObjectList(iterable $operations) {
        foreach ($operations as $operation) {
            if (!($operation instanceof AsyncOperationInterface)) {
                throw new PortaException("Objects for async call should implement AsyncOperationInterface");
            }
        }
    }

}
