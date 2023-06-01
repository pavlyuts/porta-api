<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Exceptions;

use GuzzleHttp\Psr7\Response;
use Porta\Billing\Exceptions\PortaException;
use Porta\Billing\Exceptions\PortaAuthException;

/**
 * Exception to throw on specfied Portaone Billing API error
 *
 */
class PortaApiException extends PortaException {

    protected $portaCode;

    public static function createFromResponse(Response $response) {
        if ((500 == $response->getStatusCode()) &&
                !is_null($err = @json_decode((string) $response->getBody(), true)) &&
                isset($err['faultcode']) &&
                isset($err['faultstring'])) {
            return new self($err['faultstring'], $err['faultcode']);
        }
        return new PortaException("Request returned error {$response->getStatusCode()}, '{$response->getReasonPhrase()}'", $response->getStatusCode());
    }

    public function __construct(string $message = "", string $portaCode = "") {
        $this->portaCode = $portaCode;
        parent::__construct($message);
    }

    public function getPortaCode() {
        return $this->portaCode;
    }

    public function __toString(): string {
        return __CLASS__ . ": {$this->message}, error code '{$this->portaCode}' in {$this->file}:{$this->line}\n"
                . "Stack trace:\n{$this->getTraceAsString()}";
    }

}
