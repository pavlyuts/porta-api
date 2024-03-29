<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Session;

use Porta\Billing\Interfaces\SessionStorageInterface;

/**
 * Dummy storage to use when no persistent session need.
 *
 * Do nothing, store nothing, Billing class logins each time as instantiated.
 * Used by default if no storage class given.
 *
 * @api
 * @package SessionStorage
 */
class SessionNoStorage implements SessionStorageInterface {

    public function clean(): void {

    }

    public function load(): ?array {
        return null;
    }

    public function startUpdate(): bool {
        return true;
    }

    public function save(array $session): void {

    }

}
