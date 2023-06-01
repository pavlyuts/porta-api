<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Components;

use Porta\Billing\PortaConfigInterface;
use Porta\Billing\Session\SessionStorageInterface;
use Porta\Billing\Exceptions\PortaException;
use GuzzleHttp\Psr7\Response;

/**
 * Base class implementing the shared functions of API and ESPF
 *
 * @api
 */
abstract class BillingBase {

    /**
     * Billing API datetime format
     * @api
     */
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';

    protected PortaConfigInterface $config;
    protected SessionClient $client;
    protected SessionManager $session;

    /**
     * Setup biling wrapper, load (if any) saved session state and get class ready to use
     *
     * On construct:
     * - Loads saved session data from given SessinStorage object
     * - Check the session token expire time within margin or left
     * - If token expire soon (within configured margin), try to refresh token
     * - If token expired or refresh failed - try to relogin if account data present, throwing exceptions on failures.
     * - If no accoount data present, just left the class un-logged-in, then you need login() to get it connected
     *
     * @param PortaConfigInterface $config
     * @param SessionStorageInterface|null $storage
     * @api
     */
    public function __construct(PortaConfigInterface $config, ?SessionStorageInterface $storage = null) {
        $this->config = $config;
        $this->client = new SessionClient($config);
        $this->session = new SessionManager($this->config, $this->client, $storage);
    }

    /**
     * Do login and setup the session.
     *
     * The session data will be stored if session storage class is supplied
     *
     * @param array $account The same structure as used in PortaConfig, associative array for 'login'+'password' or 'login'+'token' keys
     * @throws \Porta\Billing\Exceptions\PortaConnectException
     * @throws \Porta\Billing\Exceptions\PortaAuthException
     * @api
     */
    public function login(array $account): void {
        $this->session->login($account);
    }

    /**
     * Closes the session explicitly.
     *
     * Will call '/Session/logout' api method. Due of some problems with Portaone resposes, session will considered closed whatever server respond. No server error will be respected, but conenction errors will thrown.
     *
     * @throws \Porta\Billing\Exceptions\PortaConnectException
     * @api
     */
    public function logout(): void {
        $this->session->logout();
    }

    /**
     * Return true if session is up
     *
     * Returns true if session data (token) exists and not expired, false if
     * not logged in for any reason.
     *
     * @return bool
     * @api
     */
    public function isSessionUp(): bool {
        return $this->session->isSessionUp();
    }

    /**
     * Return current user login name
     *
     * Returns username taken from access token or null if no session is up
     *
     * @return string|null - username
     * @api
     */
    public function getUsername(): ?string {
        return $this->session->getUsername();
    }

    protected function request(string $method, $uri = '', array $options = []): Response {
        if ($this->isSessionUp()) {
            $response = $this->client->request($method, $this->buildUri($uri), $options);
            if (200 == $response->getStatusCode()) {
                return $response;
            }
            if (!$this->isAuthError($response)) {
                $this->processPortaError($response);
            }
        }
        $this->session->relogin();
        $response = $this->client->request($method, $this->buildUri($uri), $options);
        if (200 != $response->getStatusCode()) {
            $this->processPortaError($response);
        }
        return $response;
    }

    protected function requestAsync(string $method, $uri = '', array $options = []): \GuzzleHttp\Promise\PromiseInterface {
        return $this->client->requestAsync($method, $this->buildUri($uri), $options);
    }

    protected function buildUri(string $endpoint): string {
        return $this->getPathBase()
                . (('/' == substr($endpoint, 0, 1)) ? '' : '/')
                . $endpoint;
    }

    /**
     * Extracts body from response object and decode it to array
     *      *
     * @param Response $response
     * @return array
     * @throws PortaApiException in a case of can't decode
     */
    protected static function jsonResponse(Response $response): array {
        if (0 == $response->getBody()->getSize()) {
            return [];
        }
        $result = json_decode($response->getBody(), true);
        if (is_null($result)) {
            throw new PortaException("Can't decode returned JSON");
        }
        return $result;
    }

    abstract protected function getPathBase(): string;

    abstract protected function processPortaError(Response $response): void;

    abstract protected function isAuthError(Response $response): bool;

    /**
     * Convert billing-supplied UTC time string to DateTime object with target
     * timezone
     *
     * @param string $billingTime - datetime string as billing returns
     * @param string $timezone - timezone string like 'Europe/London" or '+3000',
     *                         as defined at https://www.php.net/manual/en/datetimezone.construct.php
     * @return \DateTime
     * @api
     */
    public static function timeToLocal(string $billingTime, string $timezone = 'UTC'): \DateTime {
        return (new \DateTime($billingTime, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($timezone));
    }

    /**
     * Return billing API string at UTC fro given Datetime object
     *
     * The DateTime obhject given will not change.
     *
     * @param \DateTimeInterface $time Datetime object to take time from
     * @return string Billing API-type datetime string, shifted to UTC
     * @api
     */
    public static function timeToBilling(\DateTimeInterface $time): string {
        return $time->setTimezone(new \DateTimeZone('UTC'))->format(self::DATETIME_FORMAT);
    }

}