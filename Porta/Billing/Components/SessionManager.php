<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Components;

use Porta\Billing\Interfaces\ConfigInterface;
use Porta\Billing\Components\SessionClient;
use Porta\Billing\Interfaces\SessionStorageInterface;
use Porta\Billing\Session\SessionNoStorage;
use Porta\Billing\Exceptions\PortaException;
use Porta\Billing\Exceptions\PortaApiException;
use Porta\Billing\Exceptions\PortaAuthException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions as RO;

/**
 * Class to handle session
 *
 * @internal
 */
class SessionManager {

    protected const TOKEN_GOOD = 'token_good';
    protected const TOKEN_IN_MARGIN = 'need_refresh';
    protected const TOKEN_EXPIRED = 'need_relogin';

    protected SessionClient $client;
    protected ConfigInterface $config;
    protected SessionData $sessionData;
    protected SessionStorageInterface $storage;

    public function __construct(ConfigInterface $config, SessionClient $client, SessionStorageInterface $storage = null) {
        $this->client = $client;
        $this->config = $config;
        $this->sessionData = new SessionData();
        $this->storage = $storage ?? new SessionNoStorage();
        $this->client->setSesson($this);
        $this->sessionLoad();
    }

    public function login(array $account): void {
        $this->config->setAccount($account);
        $limit = microtime(true) + 0.5;
        while (!$this->storage->startUpdate()) {
            usleep(20000);
            if (microtime(true) > $limit) {
                throw new PortaException("Unable to lock sesson storage during login attempt");
            }
        }
        $this->processLogin();
    }

    public function getUsername(): ?string {
        return $this->sessionData->getTokenDecoder()->getLogin();
    }

    /**
     * Does active sesson check to billing server, relogin if required
     *
     * Completes 'Session/ping' call to check session state, then:
     *
     * - If session not recognised, and ccredentials present, trying to relogin
     * - If no credentials in config or login failure - throws auth exception
     *
     * @throws PortaAuthException
     */
    public function checkSession(): void {
        if ($this->isSessionPresent()) {
            $response = $this->client->request('POST', $this->config->getAPIPath() . '/Session/ping',
                    [RO::JSON => [SessionClient::PARAMS => [SessionData::ACCESS_TOKEN => $this->sessionData->getAccessToken()]]]);
            if (200 != $response->getStatusCode()) {
                throw PortaApiException::createFromResponse($response);
            }
            $answer = SessionClient::jsonResponse($response);
            if (0 < ($answer['user_id'] ?? 0)) {
                return;
            }
        }
        $this->relogin();
    }

    public function relogin(): void {
        if (!$this->config->hasAccount()) {
            throw new PortaAuthException("Have no credentials to restore session");
        }
        if (!$this->storage->startUpdate()) {
            $this->sessionData->setData($this->storage->load());
            return;
        }
        $this->processLogin();
    }

    /**
     * Closes the session explicitly
     */
    public function logout(): void {
        if (!$this->sessionData->isSet()) {
            return;
        }
        try {
            $this->client->request('POST', $this->config->getAPIPath() . '/Session/logout',
                    [RO::JSON => [SessionClient::PARAMS => [SessionData::ACCESS_TOKEN => $this->sessionData->getAccessToken()]]]);
        } catch (PortaException $ex) {
            if ($ex instanceof \Porta\Billing\Exceptions\PortaConnectException) {
                throw $ex;
            }
        }
        $this->sessionDrop();
    }

    public function isSessionPresent(): bool {
        return $this->sessionData->isSet();
    }

    protected function processLogin(): void {
        $response = $this->client->request('POST',
                $this->config->getAPIPath() . '/Session/login',
                [RO::JSON => [SessionClient::PARAMS => $this->config->getAccount()]]
        );

        if (200 == $response->getStatusCode()) {
            $this->sessionUpdate(SessionClient::jsonResponse($response));
            return;
        }
        $this->sessionDrop();
        if (self::isLoginFailed($response)) {
            throw PortaAuthException::createWithAccount($this->config->getAccount());
        }
        throw PortaApiException::createFromResponse($response);
    }

    public function refreshToken(): bool {
        if (!$this->storage->startUpdate()) {
            $this->sessionData->updateData($this->storage->load());
            return true;
        }

        $response = $this->client->request('POST',
                $this->config->getAPIPath() . '/Session/refresh_access_token',
                [RO::JSON => [SessionClient::PARAMS => [$this->sessionData::REFRESH_TOKEN => $this->sessionData->getRefreshToken()]]]
        );

        if (200 == $response->getStatusCode()) {
            $this->sessionUpdate(SessionClient::jsonResponse($response));
            return true;
        }
        $this->sessionDrop();
        return false;
    }

    protected function checkToken(): string {
        $dt = $this->sessionData->getAccessTokenExpire()->getTimestamp() - (new \DateTime())->getTimestamp();
        return ($dt < 0) //
                ? self::TOKEN_EXPIRED //
                : (($dt < $this->config->getSessionRefreshMargin()) //
                ? self::TOKEN_IN_MARGIN //
                : self::TOKEN_GOOD);
    }

    protected static function isLoginFailed(Response $response): bool {
        return (500 == $response->getStatusCode()) &&
                ('Server.Session.auth_failed' == (SessionClient::jsonResponse($response)['faultcode'] ?? ''));
    }

    public function getAuthHeader() {
        return ($this->sessionData->isSet()) ?
                ['Authorization' => 'Bearer ' . $this->sessionData->getAccessToken()] :
                [];
    }

    protected function sessionLoad(): void {
        $this->sessionData->setData($this->storage->load());
        if ($this->isSessionPresent()) {
            switch ($this->checkToken()) {
                case self::TOKEN_GOOD:
                    return;
                case self::TOKEN_IN_MARGIN:
                    if ($this->refreshToken()) {
                        return;
                    }
            }
            $this->sessionDrop();
        }
        if ($this->config->hasAccount()) {
            $this->relogin();
        }
    }

    protected function sessionUpdate(array $session): void {
        $this->sessionData->updateData($session);
        $this->storage->save($this->sessionData->getData());
    }

    protected function sessionDrop(): void {
        $this->sessionData->setData();
        $this->storage->clean();
    }

}
