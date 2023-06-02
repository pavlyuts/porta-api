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
 * Use file as session data storage.
 *
 * This storage is useful for server applications which use one account to access the billing and share the session auth data.
 *
 * It will do the best to lock from concurent processes on sesson refresh, but there no way to guarantee the lock. Please, let me know in a cse you really use it under high concurrent load.
 *
 * @package SessionStorage
 * @api
 */
class SessionFileStorage implements SessionStorageInterface {

    const UPD_POSTFIX = '.update';

    protected string $fileName;
    protected float $timeout;
    protected $lock = false;

    public function __destruct() {
        if ($this->lock) {
            $this->removeLock();
        }
    }

    /**
     * Setup storage to use file
     *
     * @param string $fileName name of the file to use as persistent session storage
     * @param int $timeout file lock timeout in milliseconds
     * @api
     */
    public function __construct(string $fileName, int $timeout = 300) {
        $this->fileName = $fileName;
        $this->timeout = $timeout / 1000;
    }

    /** @inherit */
    public function clean(): void {
        if (file_exists($this->fileName)) {
            @unlink($this->fileName);
        }
    }

    /** @inherit */
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

    /** @inherit */
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

    /** @inherit */
    public function save(array $session): void {
        if (!$this->lock) {
            throw new PortaException("Must lock before save");
        }
        $this->removeLock();
        if (false === @file_put_contents($this->fileName, json_encode($session, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT))) {
            throw new PortaException("Can't write session data file {$this->fileName}");
        }
    }

    /** @inherit */
    protected function setLock() {
        if (!@touch($this->fileName . self::UPD_POSTFIX)) {
            throw new PortaException("Can't write session data lock file " . $this->fileName . self::UPD_POSTFIX);
        }
    }

    /** @inherit */
    protected function isLocked() {
        return file_exists($this->fileName . self::UPD_POSTFIX);
    }

    /** @inherit */
    protected function waitUnlock() {
        $t = microtime(true) + $this->timeout;
        while ($this->isLocked()) {
            usleep(5000);
            if (microtime(true) > $t) {
                $this->removeLock();
            }
        }
    }

    /** @inherit */
    protected function removeLock() {
        if (file_exists($this->fileName . self::UPD_POSTFIX)) {
            unlink($this->fileName . self::UPD_POSTFIX);
            $this->lock = false;
        }
    }

}
