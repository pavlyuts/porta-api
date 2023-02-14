<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Traits;

use PortaApi\Session\Session;
use PortaApi\Session\SessionStorageInterface;

/**
 * Trait to use Session
 */
trait SessionTrait {

    protected $session;

    protected function setupSession(array $config, SessionStorageInterface $storage = null) {
        $this->session = new Session($config, $storage);
    }

    /**
     * Do login and setup the session. The session data will be stored if session 
     * storage class is supplied
     * 
     * @param array $account - the same structure as used in config
     */
    public function login(array $account) {
        $this->session->login($account);
    }

    /**
     * Closes the session explicitly
     */
    public function logout() {
        $this->session->logout();
    }

    /**
     * Returns true if session data (token) exists and not expired, false if 
     * not logged in
     * 
     * @return bool
     */
    public function isSessionUp(): bool {
        return $this->session->isSessionUp();
    }

    /**
     * Returns username taken from access token or null if no session up
     * 
     * @return string|null - username
     */
    public function getUsername(): ?string {
        return $this->session->getUsername();
    }

    protected function getAuthHeader(): array {
        return $this->session->getAuthHeader();
    }

    protected function relogin() {
        $this->session->relogin();
    }

}
