<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Session;

use PortaApi\Session\SessionNoStorage;

/**
 * Test dumb storage 
 */
class SessionNoStorageTest extends \PHPUnit\Framework\TestCase {

    public function testAll() {
        $s = new SessionNoStorage();
        $this->assertTrue($s->startUpdate());
        $this->assertNull($s->save([]));
        $this->assertNull($s->load());
        $this->assertNull($s->clean());
    }

}
