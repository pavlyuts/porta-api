<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing;

use Porta\Billing\Components\BillingBase;
use Porta\Billing\Components\SessionClient;
use Porta\Billing\Interfaces\ConfigInterface;
use Porta\Billing\Interfaces\SessionStorageInterface;
use Porta\Billing\Interfaces\AsyncOperationInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Header;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Porta\Billing\Exceptions\PortaException;
use Porta\Billing\Exceptions\PortaApiException;
use Porta\Billing\Exceptions\PortaAuthException;
use Porta\Billing\Exceptions\PortaConnectException;

/**
 * Billing API wrapper
 *
 * The class is intended to provide interface to Portaone billing API. It handles authorisation, access token management and API call hndling.
 * It needs:
 * - PortaConfigInterface object as billing server, account and call options configuration.
 * - SessionStorageInterface object to store session data between invocanions.
 *
 * See 'API documentation' section on https://docs.portaone.com
 * @api
 */
class Billing extends BillingBase {

    /**
     * @inherit
     * @api
     */
    public function __construct(ConfigInterface $config, ?SessionStorageInterface $storage = null) {
        parent::__construct($config, $storage);
    }

    /**
     * Perform billing API call.
     *
     * Reference to your billing system API docs, located
     * at https://your-billing-sip-host/doc/api/ or API docs section of Portaone
     * docs site https://docs.portaone.com/. Mind your billing release version.
     *
     * @param string $endpoint API endpoint as per docs, exapmle: '/Customer/get_customer_info'
     * @param array $params API requst data to put into "params" section
     *
     * @return array Billing system answer, converted to associative array. If billing retuns file, returns array with keys:
     * 'filename' => string, returned file name,
     * 'mime' => string, MIME file type
     * 'stream' => PSR-7 stream object with file
     *
     * @throws PortaException on general errors
     * @throws PortaAuthException on auth-related errors
     * @throws PortaApiException on API returned an error
     * @api
     */
    public function call(string $endpoint, array $params = []): array {
        return static::processResponse(
                        $this->request('POST', $endpoint, self::prepareParamsJson($params))
        );
    }

    /**
     * Perform bulk async billig call, running multiple concurrent requests at once.
     *
     * Method taking an array or any other traversable of of AsyncOperationInterface objects an process it in parallel with given concurrency (default 20). After run, the objects filled with answers or exceptions depending of each separate call results.
     *
     * To avoid time wasting and return of all object filled with error due of broken sesson state, it validate session to the billing before the bulk call throw exception if session is not valid. This means one extra call to the billing before each bulk call start.
     *
     * @param AsyncOperationInterface[] $operations array or any other traversable list of  of
     *        objects, implementing BillingAsyncOperationInterface
     *
     * @param int $concurency how much calls to run in parallel. Default is 20. <i>WARNING: due of some reasons increasing of concurrency does not decrease overall time to complete all the requests. In fact, PHP does not support async operations, so all the magic comes from cURL multi-call.</i>
     *
     * @throws PortaAuthException
     * $api
     */
    public function callAsync(iterable $operations, int $concurency = 20) {
        /** @var AsyncOperationInterface[] $operations */
        $this->checkAsyncObjectList($operations);
        if (!$this->session->checkSession()) {
            throw new PortaAuthException("No active session to run async request");
        }

        $tasksIterator = function () use ($operations) {
            /** @var AsyncOperationInterface[] $operations */
            foreach ($operations as $operation) {
                if (null === $operation->getCallEndpoint()) {
                    continue;
                } else {
                    $func = function () use ($operation) {
                        /** @var AsyncOperationInterface[] $operations */
                        //$callData = $operations[$index]->getCall();
                        return $promise = $this->requestAsync(
                                'POST',
                                $operation->getCallEndpoint(),
                                Billing::prepareParamsJson($operation->getCallParams())
                        )
                        ->then(
                        function (Response $response) use ($operation) {
                            if (200 == $response->getStatusCode()) {
                                try {
                                    $operation->processResponse(static::processResponse($response));
                                } catch (PortaException $ex) {
                                    $operation->processException($ex);
                                }
                            } else {
                                $operation->processException(PortaApiException::createFromResponse($response));
                            }
                        },
                        function (\GuzzleHttp\Exception\ConnectException $reason) use ($operation) {
                            $operation->processException(new PortaConnectException($reason->getMessage(), $reason->getCode()));
                        }
                        );
                    };
                    yield $func;
                }
            }
        };
        $pool = new Pool($this->client, $tasksIterator(), ['concurrency' => $concurency]);
        $promise = $pool->promise();
        $promise->wait();
    }

    /**
     * Call and return the next-level array if there only one key on the first level
     *
     * Same as call(), but if API returns an array with one element (mostly answers
     * has just one key like 'customer_list'), it will cut one level and return the list
     * itself. If the Billing returns more that one element on the top level, it returns
     * the whole array. Sample: instead of ['customer_list' => [{customer data here}]]
     * it will return [{customer data here}] array directly.
     *
     * Use with care as the retorn depends of API call params and may be unclear.
     *
     * @param string $endpoint API endpoint as per docs, exapmle: '/Customer/get_customer_info'
     * @param array $params API requst data to put into "params" section
     *
     * @return array Billing system answer, converted to associative array, cut one level if the returned array has only one key on the top level.
     *               If billing retuns file, returns array of two keys:
     *               'filename' => string, returned file name,
     *               'mime' => string, content-type,
     *               'stream' => PSR-7 stream object with file
     *
     * @throws PortaException on general errors
     * @throws PortaAuthException on auth-related errors
     * @throws PortaApiException on API returned an error
     * @api
     */
    public function callList(string $endpoint, array $params = []) {
        $answer = $this->call($endpoint, $params);
        $keys = array_keys($answer);
        return (count($keys) > 1) ? $answer : $answer[$keys[0]];
    }

    protected static function processResponse(Response $response) {
        switch (static::detectContentType($response)) {
            case 'application/json':
                return static::jsonResponse($response);
            case 'application/pdf':
            case 'application/csv':
                return static::extractFile($response);
            default:
                throw new PortaException("Missed or unknown content-type '" . static::detectContentType($response) . "'in the billing answer");
        }
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

    protected function getPathBase(): string {
        return $this->config->getAPIPath();
    }

    protected function isAuthError(Response $response): bool {
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

    protected function processPortaError(Response $response): void {
        throw PortaApiException::createFromResponse($response);
    }

    protected static function prepareParamsJson(array $params) {
        return [
            RequestOptions::JSON => [
                SessionClient::PARAMS => ([] == ($params ?? [])) ? new \stdClass() : $params,
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
