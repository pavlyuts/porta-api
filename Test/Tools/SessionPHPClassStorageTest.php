<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

/**
 Test for SessionPHPClassStorage
 *
 */
class SessionPHPClassStorageTest extends \PHPUnit\Framework\TestCase{
    
    const DATA = [
        'key' => 'val',
    ];
    
    public function testAll() {
        $s = new SessionPHPClassStorage(self::DATA);
        $this->assertEquals(self::DATA,$s->load());
        $s->clean();
        $this->assertNull($s->load());
        $s->save(self::DATA);
        $this->assertEquals(self::DATA,$s->load());
        $this->assertTrue($s->startUpdate());
        
        $s = new SessionPHPClassStorage(null, false);
        $this->assertFalse($s->startUpdate());
        
    }
}
