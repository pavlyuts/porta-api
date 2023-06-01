<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Session;

use Porta\Billing\Interfaces\SessionStorageInterface;
use Porta\Billing\Exceptions\PortaException;

/**
 * Use file as session data storage. Useful for server applications which use one
 * account to access the billing and share the session auth data.
 *
 */
class SessionFileStorage implements SessionStorageInterface {

    const UPD_POSTFIX = '.update';

    protected $fileName;
    protected $timeout;
    protected $lock = false;

    public function __destruct() {
        if ($this->lock) {
            $this->removeLock();
        }
    }

    public function __construct(string $fileName, int $timeout = 300) {
        $this->fileName = $fileName;
        $this->timeout = $timeout / 1000;
    }

    public function clean() {
        if (file_exists($this->fileName)) {
            @unlink($this->fileName);
        }
    }

    public function startUpdate(): bool {
        if ($this->lock) {
            return true;
        }
        if ($this->isLocked()) {
            return false;
        }
        $this->setLock();
        $this->lock = true;
        return true;
    }

    public function load(): ?array {
        if (!file_exists($this->fileName)) {
            return null;
        }
        $this->waitUnlock();
        $content = file_get_contents($this->fileName);
        if (null !== ($session = json_decode($content, true))) {
            return $session;
        }
        $this->clean();
        return null;
    }

    public function save(array $session) {
        if (!$this->lock) {
            throw new PortaException("Must lock before save");
        }
        $this->removeLock();
        if (false === @file_put_contents($this->fileName, json_encode($session, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT))) {
            throw new PortaException("Can't write session data file {$this->fileName}");
        }
    }

    protected function setLock() {
        if (!@touch($this->fileName . self::UPD_POSTFIX)) {
            throw new PortaException("Can't write session data lock file " . $this->fileName . self::UPD_POSTFIX);
        }
    }

    protected function isLocked() {
        return file_exists($this->fileName . self::UPD_POSTFIX);
    }

    protected function waitUnlock() {
        $t = microtime(true) + $this->timeout;
        while ($this->isLocked()) {
            usleep(5000);
            if (microtime(true) > $t) {
                $this->removeLock();
            }
        }
    }

    protected function removeLock() {
        if (file_exists($this->fileName . self::UPD_POSTFIX)) {
            unlink($this->fileName . self::UPD_POSTFIX);
            $this->lock = false;
        }
    }

}
