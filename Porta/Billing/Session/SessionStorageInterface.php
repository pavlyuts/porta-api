<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Session;

/**
 * Interface for session storage
 *
 */
interface SessionStorageInterface {

    /**
     * Cleans the billing session data from storage.
     *
     * Called after implicit logoff
     */
    public function clean();

    /**
     * Loads the session data from storage and return it
     *
     * Implementation MUST wait for lock to release if it set by concurent
     * process, lock it for reading and unlock after read the content.
     *
     * @return array|null null if no sesion data stored
     */
    public function load(): ?array;

    /**
     * Saves session data to storage. Implementation should rley that the storage
     * was locked by $this->lock() call, write and release after the lock.
     *
     * @param array $session data to save
     */
    public function save(array $session);

    /**
     * If session data stroage is shared over different processes with async start,
     * it is necessary to avoid concurent login/tocked update processes. Therefore,
     * the first process who started login or token refresh process must lock the
     * storage and neither other process should try to login or refresh outdated
     * tocken.
     *
     * PRocess calls startUpdate which should put storage in locked-for-update state
     * and return true or return false if the storage already locked for update.
     * If got 'false', the process should re-load the session data with load()
     *
     * @return bool true if got lock for update, false if other process already
     * updates the session storage
     */
    public function startUpdate(): bool;

}
