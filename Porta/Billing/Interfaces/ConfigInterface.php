<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Interfaces;

/**
 * Interface to manage billing server API cinfiguration
 *
 * Billing use this interface to know where to connect, store account, set HTTP(s) call options, e.t.c.
 *
 * @api
 * @package Configuration
 */
interface ConfigInterface {

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
     * Provides base server URI for all services like 'https://host.dom', no trailing slash.
     *
     * @return string
     * @api
     */
    public function getUrl(): string;

    /**
     * Provides API base path, default is '/rest', no trailig shash
     *
     * @return string
     * @api
     */
    public function getAPIPath(): string;

    /**
     * Provides ESPF base path, default is '/espf/v1', no trailig shash
     *
     * @return string
     * @api
     */
    public function getEspfPath(): string;

    /**
     * Returns true if accound record present in the config and correct.
     *
     * Billing casses rely on account data is checked for consistency in the ConfigInterface class.
     * Consistency mean that a pair of login+password or login+token present.
     *
     * Billing class will not check it and send as is, generating API failure if the data is wrong.
     *
     * @return bool
     * @api
     */
    public function hasAccount(): bool;

    /**
     * Provides account record or throw PortaAuthException if there no record inside.
     *
     * @return array must have a pair of keys: `account`+`password` or `account`+`token`
     * @throws PortaAuthException
     * @api
     */
    public function getAccount(): array;

    /**
     * Sets account record. Exception if the record is inconsistent
     *
     * @param array|null $account must have a pair of keys: 'account'+'password' or 'account'+'token'.
     * null to clear account record out.
     * @return self for chaining
     * @throws PortaAuthException
     * @api
     */
    public function setAccount(?array $account = null): self;

    /**
     * Provides Gizzle http call options
     * Be careful and consult Guzzle docs: <https://docs.guzzlephp.org/en/stable/request-options.html>
     *
     * @return array to be passed to Guzzle
     * @api
     */
    public function getOptions(): array;

    /**
     * Replace Guzzle http call options with a new set
     * Be careful and consult Guzzle docs: <https://docs.guzzlephp.org/en/stable/request-options.html>
     *
     * @param array $options - new options set
     * @return self - for chaining
     * @api
     */
    public function setOptions(array $options): self;

    /**
     * Returns margin to token expire time triggering token refresh procedure.
     * Default token expire is +48h from issue time, default margin is 3600 (1h)
     * and a good for an app where you have more then one call in each hour.
     *
     * Please mind that billing also has inactivity timer, 24h by default,
     * which invalidates tocken even it is not yet expired.
     *
     * @return int seconds before token expire time to trigger refresh
     * @api
     */
    public function getSessionRefreshMargin(): int;
}
