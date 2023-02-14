<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Session;

/**
 * Class to use PHP Session storage.
 * 
 * Mind that use of session locks any other php request from the same session 
 * will wait for session unlocked and this implementation rey on that.
 * 
 * If you use session_write_close() to release the session, please do it AFTER 
 * the billing ombject setup for it may login or refresh token safe way.
 *
 */
class SessionPHPSessionStorage implements SessionStorageInterface {

    const SESSION_STORAGE_NAME = 'PortaoneBillingSession';

    protected $sessionName;

    public function __construct(string $sessionName = null) {
        $this->sessionName = $sessionName;
    }

    public function clean() {
        unset($_SESSION[static::SESSION_STORAGE_NAME]);
    }

    public function startUpdate():bool {
        return true;
    }

    public function load(): ?array {
        switch (session_status()) {
            case PHP_SESSION_ACTIVE:
                break;
            case PHP_SESSION_DISABLED:
                throw new BillingException("Session is disabled, fatal error");
            case PHP_SESSION_NONE:
                if (!is_null($this->sessionName)) {
                    session_name($this->sessionName);
                }
                if (!session_start()) {
                    throw new BillingException("Failed to start session");
                }
        }
        return $_SESSION[static::SESSION_STORAGE_NAME] ?? null;
    }

    public function save(array $session) {
        $_SESSION[static::SESSION_STORAGE_NAME] = $session;
    }

}
