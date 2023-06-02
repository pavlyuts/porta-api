<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Session;

use Porta\Billing\Interfaces\SessionStorageInterface;

/**
 * Class to use PHP Session storage.
 *
 * Use of session locks any other php request with the same session.
 * It will wait for session unlocked and this implementation rely on that.
 *
 * If you use session_write_close() to release the session, please do it **after**
 * the wrapper setup for it may login or refresh token and save it safe way.
 *
 * Also, keep in mind that Billing may return auth error even session token is
 * not yet expired. When credentials given to config, SessionManager transparently
 * relogins. But if you have PHP session already released, new token will **not**
 * be stored in the session after refresh/relogin.
 *
 * @api
 * @package SessionStorage
 */
class SessionPHPSessionStorage implements SessionStorageInterface {

    const SESSION_STORAGE_NAME = 'PortaoneBillingSession';

    protected $sessionName;

    /**
     * Setup class
     *
     * @param string $sessionName Optional session name to start if it is not
     * yet started.
     *
     * @api
     */
    public function __construct(string $sessionName = null) {
        $this->sessionName = $sessionName;
    }

    public function clean() {
        unset($_SESSION[static::SESSION_STORAGE_NAME]);
    }

    public function startUpdate(): bool {
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
