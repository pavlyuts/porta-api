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
 */
class AsyncOperation implements AsyncOperationInterface {

    protected $endpoint;
    protected $params;
    protected $success = null;
    protected $response = null;
    protected $exception = null;

    public function __construct(string $endpoint, array $params = []) {
        $this->endpoint = $endpoint;
        $this->params = $params ?? [];
    }

    public function getCall(): ?array {
        return [$this->endpoint, $this->params];
    }

    public function processException(PortaException $ex) {
        $this->success = false;
        $this->exception = $ex;
    }

    public function processResponse(array $response) {
        $this->success = true;
        $this->response = $response;
    }

    public function executed(): bool {
        return !is_null($this->success);
    }

    public function success(): bool {
        return $this->success ?? false;
    }

    public function getResponse(): ?array {
        return $this->response;
    }

    public function getException(): ?PortaException {
        return $this->exception;
    }

}
