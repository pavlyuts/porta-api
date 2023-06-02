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
 * The class is intended to provide interface to Portaone billing API. It handles
 * authorisation, access token management and API call handling.
 * It needs:
 * - {@see ConfigInterface} object as billing server host, account and call options configuration.
 * - {@see SessionStorageInterface} object to store session data between invocanions.
 *
 * See 'API documentation' section on <https://docs.portaone.com>
 * @api
 * @package Billing
 */
class Billing extends BillingBase {

    /**
     * @inherit
     * @package Billing
     */
    public function __construct(ConfigInterface $config, ?SessionStorageInterface $storage = null) {
        parent::__construct($config, $storage);
    }

    /**
     * Perform billing API call.
     *
     * Reference to your billing system API docs, located
     * at **https://your-billing-sip-host/doc/api/** or API section of Portaone
     * docs site <https://docs.portaone.com/>. Mind your billing release version.
     *
     * @param string $endpoint API endpoint as per docs, exapmle: '/Customer/get_customer_info'
     * @param array $params API requst data to put into "params" section
     *
     * @return array Billing system answer, converted to associative array. If
     * billing retuns file, returns array with keys:
     * ```
     * $returned = [
     *     'filename' => 'Invoice1234.pdf', // string, returned file name,
     *     'mime' => 'application/pdf',     // string, MIME file type
     *     'stream' => Stream               // PSR-7 stream object with file
     * ];
     * ```
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
     * Method crawling recursively the traversable given to find all
     * {@see Interfaces\AsyncOperationInterface} objects and then
     * process it in parallel with given concurrency (default 20). After run, the
     * objects filled with answers or exceptions depending of each separate call
     * results. it is safe as if there no object, it just do nothing silently,
     * but still do a session check call.
     *
     * On every callAsync() will first check session state with active call to the
     * biling (/Session/ping) to avoid time wasting and return of all object filled
     * with error due of broken sesson state. If the session fail and credentials
     * present, it will try relogin. If no credential or login filed, it will throw
     * PortaAuthException.
     *
     * @param iterable $operations array or any other multi-level iterable, containing
     *        objects, implementing AsyncOperationInterface
     *
     * @param int $concurency how much calls to run in parallel. Default is 20.
     *
     * **WARNING: due of some reasons increasing of concurrency over some empiric
     * value does not decrease overall time to complete all the requests and even
     * make it longer. In fact, PHP does not support async operations, so all the
     * magic comes from cURL multi-call, so it could be combination of limitations:
     * cURL, PortaOne server and PHP itlself. As for me, 20 works fine.**
     *
     * @throws PortaAuthException in a case of sesson is expired/broken and there
     * no credentials to relogin
     * @api
     */
    public function callAsync(iterable $operations, int $concurency = 20): void {
        $this->session->checkSession();

        $tasksIterator = function () use ($operations) {
            yield from $this->renderArrayForAsyncCall($operations);
        };

        $pool = new Pool($this->client, $tasksIterator(), ['concurrency' => $concurency]);
        $promise = $pool->promise();
        $promise->wait();
    }

    protected function renderArrayForAsyncCall(iterable $operations) {
        foreach ($operations as $item) {
            if (is_iterable($item)) {
                yield from $this->renderArrayForAsyncCall($item);
            }
            if (($item instanceof AsyncOperationInterface) && !is_null($item->getCallEndpoint())) {
                yield from $this->yieldAsyncOperation($item);
            }
        }
    }

    protected function yieldAsyncOperation(AsyncOperationInterface $operation) {
        $func = function () use ($operation) {
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

    /**
     * Call and return the next-level array if there only one key on the first level
     *
     * Same as call(), but if API returns an array with one element (mostly answers
     * has just one key like 'customer_list'), it will cut one level and return the list
     * itself. If the Billing returns more that one element on the top level, it returns
     * the whole array. Sample:
     * ```
     * // Instead of:
     * $answer = [
     *     'customer_list' => [
     *         {customer data here}
     *     ]
     * ];
     * // it will return enclosed array:
     * $answer = [
     *     {customer data here}
     * ];
     * ```
     * **Use with care as the return depends of API call params and may be unexpectable**
     *
     * @param string $endpoint API endpoint as per docs, exapmle: '/Customer/get_customer_info'
     * @param array $params API requst data to put into "params" section
     *
     * @return array Billing system answer, converted to associative array, cut
     * one level if the returned array has only one key on the top level. If
     * billing retuns file, returns array with keys:
     * ```
     * $answer = [
     *     'filename' => 'Invoice1234.pdf', // string, returned file name,
     *     'mime' => 'application/pdf',     // string, MIME file type
     *     'stream' => Stream               // PSR-7 stream object with file
     * ];
     * ```
     *
     * @throws PortaException on general errors
     * @throws PortaAuthException on auth-related errors
     * @throws PortaApiException on API returned an error
     * @api
     */
    public function callList(string $endpoint, array $params = []): array {
        $answer = $this->call($endpoint, $params);
        $keys = array_keys($answer);
        return (count($keys) > 1) ? $answer : $answer[$keys[0]];
    }

    protected static function processResponse(Response $response): array {
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

    protected static function extractFile(Response $response): array {
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

    protected static function prepareParamsJson(array $params): array {
        return [
            RequestOptions::JSON => [
                SessionClient::PARAMS => ([] == ($params ?? [])) ? new \stdClass() : $params,
            ]
        ];
    }

}
