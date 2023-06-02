<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Interfaces;

use Porta\Billing\Exceptions\PortaException;

/**
 * Interface for element of bulk async call to the billing. Represents one API call task.
 *
 * How it works:
 * - callAsync() crawl given iterable recursively to find all objects, implementing AsyncOperationInterface
 * - For each object, it will get the API endpoint by calling `getCallEndpoint()` and call params by calling `getCallParams()`
 * - All the found array will be called in parallel with respect to desired concurrency level
 * - For each object:
 *     - if the call was succed, the result will put to the object by call processResponse()
 *     - in a case of failure exception object will put to the object by calling processException()
 *
 * Finally we have each object filled with call result, good or bad.
 *
 * @api
 * @package Async
 */
interface AsyncOperationInterface {

    /**
     * Should return Billing API endpoint to call
     *
     * May retirn null to bypass billing call for this element if required
     *
     * @return string|null
     * @api
     */
    public function getCallEndpoint(): ?string;

    /**
     * Should return Billing API call params, which will be placed to { "params": /HERE/ } of API call.
     *
     * @return array
     * @api
     */
    public function getCallParams(): array;

    /**
     * Will be called on success with response data array
     *
     * @param array $response the dataset, returned by billing
     * @api
     */
    public function processResponse(array $response): void;

    /**
     * Will be called on call failure with exception happened
     *
     * @param PortaException $ex exception, thrown while complete the call
     * @api
     */
    public function processException(PortaException $ex): void;
}
