<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi\Session;

use PortaApi\Session\SessionStorageInterface;
use PortaApi\Session\SessionNoStorage;
use PortaApi\Exceptions\PortaException;
use PortaApi\Exceptions\PortaApiException;
use PortaApi\Exceptions\PortaAuthException;
use PortaApi\Config as C;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions as RO;

/**
 * Class to handle session
 *
 */
class Session {

    use \PortaApi\Traits\ClientTrait;

    const SESSION_ID = 'session_id';
    const REFRESH_TOKEN = 'refresh_token';
    const EXPIRES_AT = 'expires_at';
    const ACCESS_TOKEN = 'access_token';
    //
    protected const TOKEN_GOOD = 'token_good';
    protected const TOKEN_IN_MARGIN = 'need_refresh';
    protected const TOKEN_EXPIRED = 'need_relogin';

    protected $account = [];
    protected $sessionData = null;
    protected $storage;
    protected $refreshMargin;
    protected $tokenTTL = null;

    public function __construct(array $config, SessionStorageInterface $storage = null) {
        $this->storage = $storage ?? new SessionNoStorage();
        $this->setupClient($config, C::API_BASE);
        $this->account = $config[C::ACCOUNT] ?? [];
        $this->tokenTTL = $config[C::TOKEN_TTL] ?? null;
        $this->refreshMargin = (is_null($this->tokenTTL)) ? ($config[C::REFRESH_MARGIN] ?? 3600) : 0;
        $this->sessionLoad();
    }

    public function login(array $account) {
        if (!(isset($account[C::LOGIN]) && (isset($account[C::PASSWORD]) || isset($account[C::TOKEN])))) {
            throw new PortaException("Username and (password or token) should be given");
        }
        $this->account = $account;
        $limit = microtime(true) + 0.5;
        while (!$this->storage->startUpdate()) {
            usleep(20000);
            if (microtime(true) > $limit) {
                throw new PortaException("Unable to lock sesson storage during login attempt");
            }
        }
        $this->processLogin();
    }

    public function getUsername() {
        return $this->decodeToken()['login'] ?? null;
    }

    public function checkSession() {
        if (!$this->isSessionUp()) {
            return false;
        }
        $response = $this->request('POST', '/Session/ping',
                [RO::JSON => [C::PARAMS => [self::ACCESS_TOKEN => $this->sessionData[self::ACCESS_TOKEN]]]]);
        if (200 != $response->getStatusCode()) {
            throw PortaApiException::createFromResponse($response);
        }
        $answer = self::jsonResponse($response);
        return (($answer['user_id'] ?? 0) > 0);
    }

    public function relogin() {
        if (!$this->hasCredentials()) {
            throw new PortaAuthException("Have no credentials to restore session");
        }
        if (!$this->storage->startUpdate()) {
            $this->sessionData = $this->storage->load();
            return;
        }
        $this->processLogin();
    }

    /**
     * Closes the session explicitly
     */
    public function logout() {
        if (!$this->isSessionUp()) {
            return;
        }
        $response = $this->request('POST', '/Session/logout', [RO::JSON => []]);
        if (200 !== $response->getStatusCode()) {
            throw PortaApiException::createFromResponse($response);
        }
        $this->sessionData = null;
        $this->storage->clean();
    }

    public function isSessionUp(): bool {
        return isset($this->sessionData[self::ACCESS_TOKEN]);
    }

    protected function processLogin() {
        $response = $this->loginRequest();
        if (200 == $response->getStatusCode()) {
            $this->sessionUpdate(static::jsonResponse($response));
            return;
        }
        $account = $this->account;
        $this->sessionDrop();
        if (self::isLoginFailed($response)) {
            throw PortaAuthException::createWithAccount($account);
        }
        throw PortaApiException::createFromResponse($response);
    }

    public function refreshToken(): bool {
        if (!$this->storage->startUpdate()) {
            $this->sessionData = $this->storage->load();
            return true;
        }
        $response = $this->refreshRequest();
        if (200 == $response->getStatusCode()) {
            $this->sessionUpdate(static::jsonResponse($response));
            return true;
        }
        $this->sessionDrop();
        return false;
    }

    protected function hasCredentials() {
        return isset($this->account[C::LOGIN]) && (isset($this->account[C::PASSWORD]) || isset($this->account[C::TOKEN]));
    }

    protected function checkToken(): string {
        $token = static::timeToLocal($this->sessionData[self::EXPIRES_AT], 'UTC');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $dt = $token->getTimestamp() - $now->getTimestamp();
        return ($dt < 0) ? self::TOKEN_EXPIRED : (($dt < $this->refreshMargin) ? self::TOKEN_IN_MARGIN : self::TOKEN_GOOD);
    }

    protected static function isLoginFailed(Response $response) {
        return (500 == $response->getStatusCode()) &&
                ('Server.Session.auth_failed' == (self::jsonResponse($response)['faultcode'] ?? ''));
    }

    public function getAuthHeader() {
        return (isset($this->sessionData[self::ACCESS_TOKEN])) ?
                ['Authorization' => 'Bearer ' . $this->sessionData[self::ACCESS_TOKEN]] :
                [];
    }

    protected function refreshRequest(): Response {
        return $this->request('POST',
                        '/Session/refresh_access_token',
                        [RO::JSON => [C::PARAMS => [self::REFRESH_TOKEN => $this->sessionData[self::REFRESH_TOKEN]]]]
        );
    }

    protected function loginRequest(): Response {
        return $this->request('POST',
                        '/Session/login',
                        [RO::JSON => [C::PARAMS => $this->account]]
        );
    }

    protected function sessionLoad() {
        $this->sessionData = $this->storage->load();
        if ($this->isSessionUp()) {
            switch ($this->checkToken()) {
                case self::TOKEN_GOOD:
                    return;
                case self::TOKEN_IN_MARGIN:
                    if ($this->refreshToken()) {
                        return;
                    };
            }
        }
        if ($this->hasCredentials()) {
            $this->relogin();
        }
    }

    protected function sessionUpdate(array $session) {
        $this->sessionData = array_merge($this->sessionData ?? [], $session);
        $this->storage->save($this->sessionData);
    }

    protected function sessionDrop() {
        $this->sessionData = null;
        $this->storage->clean();
    }

    protected function decodeToken(): ?array {
        if (!isset($this->sessionData[self::ACCESS_TOKEN])) {
            return null;
        }
        $parts = explode('.', $this->sessionData[self::ACCESS_TOKEN]);
        return @json_decode(base64_decode($parts[1] ?? 'null'), true);
    }

}
