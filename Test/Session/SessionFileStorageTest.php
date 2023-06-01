<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Session;

use Porta\Billing\Session\SessionFileStorage;
use Porta\Billing\Exceptions\PortaException;
use PortaApiTest\Tools\FileLocker;

/**
 * Tests for sessiom FileStorage
 */
class SessionFileStorageTest extends \PHPUnit\Framework\TestCase {

    const FILE = __DIR__ . '/../temp/session';
    const FILE_BAD = __DIR__ . '/../temp/nojson';
    const FILE_BAD_PATH = __DIR__ . '/../temp/Dir/file';
    const TEST_DATA = ['testKey' => 'testValue'];

    public static function setUpBeforeClass(): void {
        if (file_exists(self::FILE)) {
            unlink(self::FILE);
        }
        if (file_exists(self::FILE . SessionFileStorage::UPD_POSTFIX)) {
            unlink(self::FILE . SessionFileStorage::UPD_POSTFIX);
        }
    }

    public function tearDown(): void {
        if (file_exists(self::FILE . SessionFileStorage::UPD_POSTFIX)) {
            unlink(self::FILE . SessionFileStorage::UPD_POSTFIX);
        }
    }

    public function testStartUpdate() {
        if (file_exists(self::FILE)) {
            unlink(self::FILE);
        }
        $s = new SessionFileStorage(self::FILE);
        $this->assertFileDoesNotExist(self::FILE . SessionFileStorage::UPD_POSTFIX);

        //Test it setup locks and semaphore files right way
        $this->assertTrue($s->startUpdate());
        $this->assertFileExists(self::FILE . SessionFileStorage::UPD_POSTFIX);

        //And then true on another try
        $this->assertTrue($s->startUpdate());
        // --destruct must cleanup if there was no save() call
        unset($s);
        $this->assertFileDoesNotExist(self::FILE . SessionFileStorage::UPD_POSTFIX);

        //Check it return false if see other's lock adn do not remove another's lockfile
        touch(self::FILE . SessionFileStorage::UPD_POSTFIX);
        $s = new SessionFileStorage(self::FILE);
        $this->assertFalse($s->startUpdate());
        unset($s);
        $this->assertFileExists(self::FILE . SessionFileStorage::UPD_POSTFIX);
        unlink(self::FILE . SessionFileStorage::UPD_POSTFIX);
    }

    public function testSave() {
        if (file_exists(self::FILE)) {
            unlink(self::FILE);
        }
        $s = new SessionFileStorage(self::FILE);
        $s->startUpdate();
        $s->save(self::TEST_DATA);
        $this->assertFileExists(self::FILE);
        $this->assertEquals(
                json_encode(self::TEST_DATA, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT),
                file_get_contents(self::FILE));
        $this->assertFileDoesNotExist(self::FILE . SessionFileStorage::UPD_POSTFIX);
    }

    public function testLoad() {
        $s = new SessionFileStorage(self::FILE);
        $l = new FileLocker(self::FILE . SessionFileStorage::UPD_POSTFIX);

        //Test load reads correctly and wait for lock release
        $l->lock(20);
        $t = microtime(true);
        $this->assertEquals(self::TEST_DATA, $s->load());
        $this->assertGreaterThan(10, (microtime(true) - $t) * 1000);

        //Test for file not present
        $s = new SessionFileStorage(self::FILE . 'not');
        $this->assertNull($s->load());

        //Test for corrupted JSON content
        file_put_contents(self::FILE_BAD, "NoJsonHere");
        $s = new SessionFileStorage(self::FILE_BAD);
        $this->assertNull($s->load());
        $this->assertFileDoesNotExist(self::FILE_BAD);
    }

    public function testSaveExceptionNoStartUpdate() {
        $s = new SessionFileStorage(self::FILE);
        $this->expectException(PortaException::class);
        $s->save([]);
    }

    public function testClean() {
        $this->assertFileExists(self::FILE);
        $s = new SessionFileStorage(self::FILE);
        $s->clean();
        $this->assertFileDoesNotExist(self::FILE);
    }

    public function testUnwritableLocation_1() {
        $s = new SessionFileStorage(self::FILE_BAD_PATH);
        $this->expectException(PortaException::class);
        $s->startUpdate();
    }

    public function testUnwritableLocation_2() {
        //$s = new SessionFileStorageWrap(self::FILE_BAD_PATH);
        $s = new SessionFileStorageWrap(self::FILE);
        $s->startUpdate();
        $s->changeFilename(self::FILE_BAD_PATH);
        $this->expectException(PortaException::class);
        $s->save(self::TEST_DATA);
    }

    public function testTimeout() {
        $s = new SessionFileStorage(self::FILE, 30);
        $l = new FileLocker(self::FILE . SessionFileStorage::UPD_POSTFIX);
        $s->startUpdate();
        $s->save(self::TEST_DATA);
        $this->assertFileExists(self::FILE);
        $l->lock(100);
        $t = microtime(true);
        $s->load();
        $dt = (microtime(true) - $t) * 1000;
        $this->assertFalse($l->check());
        $this->assertGreaterThan(30, $dt);
        $this->assertLessThan(70, $dt);
        unset($l);
        $s->clean();
    }

}
