<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Components;

/**
 * Session data object
 *
 * @internal
 */
class SessionData implements \ArrayAccess {

    const SESSION_ID = 'session_id';
    const REFRESH_TOKEN = 'refresh_token';
    const EXPIRES_AT = 'expires_at';
    const ACCESS_TOKEN = 'access_token';

    protected array $data = [];
    protected ?PortaTokenDecoder $token = null;

    public function __construct(?array $data = []) {
        $this->setData($data);
    }

    public function setData(?array $data = []): self {
        $this->data = $data ?? [];
        $this->token = new PortaTokenDecoder($this->data[self::ACCESS_TOKEN] ?? null);
        if (!$this->token->isSet()) {
            $this->data = [];
        }
        return $this;
    }

    public function getData(): array {
        return $this->data;
    }

    public function updateData(array $data): self {
        $this->setData(array_merge($this->data, $data));
        return $this;
    }

    public function isSet(): bool {
        return [] != $this->data;
    }

    public function getAccessToken(): ?string {
        return $this[self::ACCESS_TOKEN] ?? null;
    }

    public function getAuthHeader(): array {
        return (isset($this[self::ACCESS_TOKEN])) ?
                ['Authorization' => 'Bearer ' . $this[self::ACCESS_TOKEN]] :
                [];
    }

    public function getRefreshToken() {
        return $this[self::REFRESH_TOKEN] ?? null;
    }

    public function getTokenDecoder(): PortaTokenDecoder {
        return $this->token;
    }

    public function getAccessTokenExpire(): ?\DateTimeInterface {
        return isset($this[self::EXPIRES_AT]) //
                ? new \DateTime($this[self::EXPIRES_AT]) //
                : null;
    }

    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void {
        // Read-only
    }

    public function offsetUnset($offset): void {
        // Read-only
    }

}
