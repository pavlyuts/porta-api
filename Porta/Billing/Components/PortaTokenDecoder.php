<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Components;

/**
 * Class to manage Portaone JWT token
 *
 * @internal 
 */
class PortaTokenDecoder implements \ArrayAccess {

    protected array $decoded;

    public function __construct(?string $token = null) {
        $this->setToken($token);
    }

    public function setToken(?string $token = null): self {
            $this->decoded = @json_decode(base64_decode(explode('.', $token)[1] ?? 'null'), true) ?? [];
//        if (is_null($token)) {
//            $this->decoded = [];
//        } else {
//            $this->decoded = @json_decode(base64_decode(explode('.', $token)[1] ?? 'null'), true) ?? [];
//            if (!isset($this['jti'])) {
//                $this->setToken();
//            }
//        }
        return $this;
    }

    public function isSet(): bool {
        return [] != $this->decoded;
    }

    public function getExpire(): ?\DateTimeInterface {
        return $this->toTime('exp');
    }

    public function getIssued(): ?\DateTimeInterface {
        return $this->toTime('iat');
    }

    public function getLogin() {
        return $this['login'] ?? null;
    }

    protected function toTime($offset): ?\DateTimeInterface {
        return isset($this[$offset]) ? (new \DateTime('@' . $this[$offset])) : null;
    }

    // To read decoded token fields as array
    public function offsetExists($offset): bool {
        return isset($this->decoded[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->decoded[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void {
        // Decoded token is read-only
    }

    public function offsetUnset($offset): void {
        // Decoded token is read-only
    }

}
