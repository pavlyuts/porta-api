<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Traits;

/**
 * SessionTrait wrapper fortesting
 *
 */
class SessionTraitWrap {

    use \PortaApi\Traits\SessionTrait;

    public function __construct($config, $storage) {
        $this->setupSession($config, $storage);
    }

    public function pubGetAuthHeader(): array {
        return $this->getAuthHeader();
    }

    public function pubRelogin() {
        $this->relogin();
    }

}
