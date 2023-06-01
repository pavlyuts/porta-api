<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

use PortaApi\Exceptions\PortaAuthException;

/**
 * Porta-API configuration class
 *
 */
class PortaConfig implements PortaConfigInterface {

    protected string $host;
    protected ?array $account = null;
    protected array $options;
    protected int $refreshMargin;

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

    public function getUrl(): string {
        return static::SCHEME . '://' . $this->host;
    }

    public function getAPIPath(): string {
        return static::API_BASE;
    }

    public function getEspfPath(): string {
        return static::ESPF_BASE;
    }

    public function getAccount(): array {
        if (is_null($this->account)) {
            throw new PortaAuthException("Account data required, but not exists");
        }
        return $this->account;
    }

    public function getApiUrl(): string {
        return static::SCHEME . '://' . $this->host . static::API_BASE;
    }

    public function getEspfUrl(): string {
        return static::SCHEME . '://' . $this->host . static::ESPF_BASE;
    }

    public function getOptions(): array {
        return $this->options;
    }

    public function getSessionRefreshMargin(): int {
        return $this->refreshMargin;
    }

    public function hasAccount(): bool {
        return !is_null($this->account);
    }

    public function setAccount(?array $account = null): self {
        $account = ([] == $account) ? null : $account;
        if (!is_null($account) &&
                (!isset($account[PortaConfigInterface::LOGIN]) ||
                !(
                isset($account[PortaConfigInterface::PASSWORD]) ||
                isset($account[PortaConfigInterface::TOKEN])))) {
            throw new PortaAuthException("Invalid account record provided, need login+pass or login+token");
        }
        $this->account = $account;
        return $this;
    }

    public function setOptions(array $options): self {
        $this->options = $options;
        return $this;
    }

}
