<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Session;

/**
 * Dumb storage to use when no storage given
 *
 */
class SessionNoStorage implements SessionStorageInterface {

    public function clean() {
        
    }

    public function load(): ?array {
        return null;
    }

    public function startUpdate(): bool {
        return true;
    }

    public function save(array $session) {
        
    }

}
