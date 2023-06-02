<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing;

use Porta\Billing\Interfaces\ConfigInterface;
use Porta\Billing\Exceptions\PortaAuthException;

/**
 * Porta-API configuration class
 *
 * @api
 * @package Configuration
 */
class Config implements ConfigInterface {

    protected string $host;
    protected ?array $account = null;
    protected array $options;
    protected int $refreshMargin;

    /**
     * Setup configuration object
     *
     * @param string $host Hostname/IP address of the server, no slashes, no schema,
     * but port if required. Example: `bill-sip.mycompany.com`
     * @param array|null $account Account record to login to the billing. Combination
     * of login+password or login+token required
     * ```
     * $account = [
     *     'login' => 'myUserName',    // Mandatory username
     *     'password' => 'myPassword', // When login with password
     *     'token' => 'myToken'        // When login with API token
     * ```
     * @param array $options oprions, passed to Guzzle HTTP client.
     * Check [Guzzle docs](https://docs.guzzlephp.org/en/stable/request-options.html)
     *
     * Please, mind that Guzze options only apply at the moment of wrapper class
     * creation by it's __construct(), so if options set **after** the wrapper
     * created - it will not be used.
     *
     * @param int $refreshMargin margin in seconds before token expire time to
     * trigger token refresh procedure.
     *
     * @throws Porta\Billing\Exceptions\PortaAuthException
     * @api
     */
    public function __construct(
            string $host,
            ?array $account = null,
            array $options = [],
            int $refreshMargin = 3600) {

        $this->host = $host;
        $this->setAccount($account);
        $this->options = $options;
        $this->refreshMargin = $refreshMargin;
    }

    /**
     * @inherit
     * @api
     */
    public function getUrl(): string {
        return static::SCHEME . '://' . $this->host;
    }

    /**
     * @inherit
     * @api
     */
    public function getAPIPath(): string {
        return static::API_BASE;
    }

    /**
     * @inherit
     * @api
     */
    public function getEspfPath(): string {
        return static::ESPF_BASE;
    }

    /**
     * @inherit
     * @api
     */
    public function getAccount(): array {
        if (is_null($this->account)) {
            throw new PortaAuthException("Account data required, but not exists");
        }
        return $this->account;
    }

    /**
     * @inherit
     * @api
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * @inherit
     * @api
     */
    public function getSessionRefreshMargin(): int {
        return $this->refreshMargin;
    }

    /**
     * @inherit
     * @api
     */
    public function hasAccount(): bool {
        return !is_null($this->account);
    }

    /**
     * @inherit
     * @api
     */
    public function setAccount(?array $account = null): self {
        $account = ([] == $account) ? null : $account;
        if (!is_null($account) &&
                (!isset($account[ConfigInterface::LOGIN]) ||
                !(
                isset($account[ConfigInterface::PASSWORD]) ||
                isset($account[ConfigInterface::TOKEN])))) {
            throw new PortaAuthException("Invalid account record provided, need login+pass or login+token");
        }
        $this->account = $account;
        return $this;
    }

    /**
     * @inherit
     * @api
     */
    public function setOptions(array $options): self {
        $this->options = $options;
        return $this;
    }

}
