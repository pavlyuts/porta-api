<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;
use Porta\Billing\Session\SessionStorageInterface;

/**
 * Store session in PHP class instance, mostly for teesting
 *
 */
class SessionPHPClassStorage implements SessionStorageInterface {

    protected $storage;
    protected $allowUpdate;

    public function __construct(array $session = null, bool $allowUpdate = true) {
        $this->storage = $session;
        $this->allowUpdate = $allowUpdate;
    }

    public function clean() {
        $this->storage = null;
    }

    public function load(): ?array {
        return $this->storage;
    }

    public function save(array $session) {
        $this->storage = $session;
    }

    public function startUpdate(): bool {
        return $this->allowUpdate;
    }

}
