<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Session;

/**
 * Test wrapper for SessionFileStorage
 *
 */
class SessionFileStorageWrap extends \PortaApi\Session\SessionFileStorage {

    public function changeFilename($fileName) {
        $this->fileName = $fileName;
    }

}
