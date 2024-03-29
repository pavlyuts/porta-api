<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Interfaces;

/**
 * Interface for session storage
 *
 * @api
 * @package SessionStorage
 */
interface SessionStorageInterface {

    /**
     * Cleans the billing session data from storage.
     *
     * Called after implicit logoff
     * @api
     */
    public function clean(): void;

    /**
     * Loads the session data from storage and return it
     *
     * Implementation MUST wait for lock to release if it set by concurent
     * process, lock it for reading and unlock after read the content.
     *
     * @return array|null null if no sesion data stored
     * @api
     */
    public function load(): ?array;

    /**
     * Saves session data to storage. Implementation should rely that the storage
     * was locked by $this->lock() call, write and release after the lock.
     *
     * @param array $session data to save
     * @api
     */
    public function save(array $session): void;

    /**
     * Called by biling session manager process when it start to refresh token/relogin
     *
     * If session data stroage is shared over different processes with async start,
     * it is necessary to avoid concurent login/tocken update processes. Therefore,
     * the first process who started login or token refresh process must lock the
     * storage and neither other process should try to login or refresh outdated
     * tocken.
     *
     * SessionManager calls startUpdate which should put storage in locked-for-update state
     * and return true or return false if the storage already locked for update.
     * If got 'false', the process should re-load the session data with load()
     *
     * @return bool - true if got lock for update,
     * - false if other process already updates the session storage
     * @api
     */
    public function startUpdate(): bool;
}
