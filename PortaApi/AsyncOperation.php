<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

use PortaApi\Exceptions\PortaException;

/**
 * Class to use with billign async operation
 *
 * Out-of-the-box Implementation of AsyncOperationInterface.
 * @api
 */
class AsyncOperation implements AsyncOperationInterface {

    protected string $endpoint;
    protected array $params;
    protected ?bool $success = null;
    protected ?array $response = null;
    protected ?PortaException $exception = null;

    /**
     * Setup aync operation data
     *
     * @param string $endpoint Billing API endpoint like '/Customer/get_customer_info'
     * @param array $params Billing API call params, which will be placed to { "params": /HERE/ } of API call
     * @api
     */
    public function __construct(string $endpoint, array $params = []) {
        $this->endpoint = $endpoint;
        $this->params = $params ?? [];
    }

    /** @inherit */
    public function getCallEndpoint(): ?string {
        return $this->endpoint;
    }

    /** @inherit */
    public function getCallParams(): array {
        return $this->params;
    }

    /** @inherit */
    public function processException(PortaException $ex): void {
        $this->success = false;
        $this->exception = $ex;
    }

    /** @inherit */
    public function processResponse(array $response): void {
        $this->success = true;
        $this->response = $response;
    }

    /**
     * Return true if the call was executed
     *
     * @return bool
     * @api
     */
    public function executed(): bool {
        return !is_null($this->success);
    }

    /**
     * Return true if call was success
     *
     * @return bool
     * @api
     */
    public function success(): bool {
        return $this->success ?? false;
    }

    /**
     * Will return resul array or success call.
     *
     *
     * @return array|null billing call dataset array, nul if called before processed
     * @api
     */
    public function getResponse(): ?array {
        return $this->response;
    }

    /**
     * Will return exception object of failed call
     *
     * @return PortaException|null exception, happened ahile processing the call. May return null if called before processed
     * @api
     */
    public function getException(): ?PortaException {
        return $this->exception;
    }

}
