<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

/**
 * Class to test buypass of AsyncOperation
 *
 */
class AsyncOperationNull extends \PortaApi\AsyncOperation {

    public function __construct() {
        parent::__construct('');
    }

    public function getCallEndpoint(): ?string {
        return null;
    }

}
