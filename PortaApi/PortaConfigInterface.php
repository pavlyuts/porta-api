<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

/**
 * Interfaceto manage billing API cinfiguration
 *
 * @api
 */
interface PortaConfigInterface {

    /**
     * Array key for login/username of account array
     * @api
     */
    public const LOGIN = 'login';

    /**
     * Array key for password of account array. Sould be used either password or
     * token
     * @api
     */
    public const PASSWORD = 'password';

    /**
     * Array key for token of account array. Sould be used either password or
     * token
     * @api
     */
    public const TOKEN = 'token';

    /**
     * override in a case of need to use other scheme, example 'http'
     * @api
     */
    public const SCHEME = 'https';

    /**
     * override to use other API base on the same host
     * @api
     */
    public const API_BASE = '/rest';

    /**
     * override to use other ESPF base on the same host
     * @api
     */
    public const ESPF_BASE = '/espf/v1';

    /**
     * Provides full URI for service like 'https://host.dom/rest', for API service.
     * no trailing slash.
     *
     * @return string
     * @api
     */
    public function getUrl(): string;

    public function getAPIPath(): string;

    public function getEspfPath(): string;

    /**
     * Returns true if accound record present in the config
     *
     * @return bool
     * @api
     */
    public function hasAccount(): bool;

    /**
     * Provides account record or throw Exception if there no record inside
     *
     * @return array
     * @api
     */
    public function getAccount(): array;

    /**
     * Sets account record. Exception if no required items given
     *
     * @param array $account with fields keyed with sef::ACCOUNT_XXX consts
     * @return self for chaining
     * @throws PortaAuthException
     * @api
     */
    public function setAccount(?array $account = null): self;

    /**
     * Provides Gizzle http call options
     * Be careful and consult Guzzle docs: https://docs.guzzlephp.org/en/stable/request-options.html
     *
     * @return array to be passed to Guzzle
     * @api
     */
    public function getOptions(): array;

    /**
     * Set/replace Guzzle http call options
     * Be careful and consult Guzzle docs: https://docs.guzzlephp.org/en/stable/request-options.html
     *
     * @param array $options - new options set
     * @return self - for chaining
     * @api
     */
    public function setOptions(array $options): self;

    /**
     * Returns margin to token expire time to trigger token refresh procedure.
     * Default token expire is +48h from issue time, default margin is 3600 (1h)
     * and a good for an app where you have more then one call in each hour.
     *
     * Please mind that billing also has inactivity timer, 24h by default,
     * which invalidates tocken even it is not yet expired.
     *
     * @return int
     * @api
     */
    public function getSessionRefreshMargin(): int;
}
