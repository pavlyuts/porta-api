<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

use PortaApi\Exceptions\PortaException;

/**
 * Interface for billing operation for bulc asyn call
 */
interface AsyncOperationInterface {

    /**
     * Return request to billing in a form of array of two elements:
     * [0] string, Billing API endpoint to call
     * [1] array, params to use with biilling call
     * 
     * must retirn null to bypass billing call for this element
     * 
     * @return array|null
     */
    public function getCall(): ?array;

    /**
     * Here the response from billing will put on success call
     * 
     * @param array $response
     */
    public function processResponse(array $response);

    /**
     * If any exception happens with call of this instance, exception will put here
     * 
     * @param PortaException $ex
     */
    public function processException(PortaException $ex);
}
