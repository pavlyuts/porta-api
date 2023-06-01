<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Session;

/**
 * Dummy storage to use when no storage given.
 *
 * Do nothing, store nothing, Billing class logins each time as instantiated.
 * Used by default if no storage class given.
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
