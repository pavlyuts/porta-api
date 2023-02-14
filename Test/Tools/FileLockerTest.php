<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

use PortaApiTest\Tools\FileLocker;

/**
 * Description of FileLockerTest
 *
 */
class FileLockerTest extends \PHPUnit\Framework\TestCase {

    const TEST_FILE_NAME = __DIR__ . '/../temp/FileLockerTestFile';
    const LOG_FILE_NAME = __DIR__ . "/../temp/FileLocker.log";

    public static function setUpBeforeClass(): void {
        if (file_exists(self::LOG_FILE_NAME)) {
            unlink(self::LOG_FILE_NAME);
        }
    }

    public function testLock() {

        $locker = new FileLocker(self::TEST_FILE_NAME);

        $locker->lock(20);
        $t = microtime(true);
        $this->waitForUnlock(100);
        $dt = (microtime(true) - $t) * 1000;
        $this->assertGreaterThan(10, $dt);
        unset($locker);
    }

    public function testCheck() {
        $locker = new FileLocker(self::TEST_FILE_NAME);
        $this->assertFalse($locker->check());
        touch(self::TEST_FILE_NAME);
        $this->assertTrue($locker->check());
        unlink(self::TEST_FILE_NAME);
        $this->assertFalse($locker->check());
    }

    public function testLockerCleanup() {
        $locker = new FileLocker(self::TEST_FILE_NAME);
        $locker->lock(200);
        $this->assertTrue($this->isLocked());
        unset($locker);
        $this->assertFalse($this->isLocked());
    }

    public function testBadParamException_1() {
        $locker = new FileLocker(self::TEST_FILE_NAME);
        $this->expectException(\Exception::class);
        $locker->lock(-1);
    }

    public function testBadParamException_2() {
        $locker = new FileLocker('/Dire/Not?exist/file');
        $this->expectException(\Exception::class);
        $locker->lock(10);
    }

    protected function isLocked() {
        return file_exists(self::TEST_FILE_NAME);
    }

    protected function waitForUnlock(int $t) {
        $dt = microtime(true) + ($t / 1000);
        while ($this->isLocked()) {
            if (microtime(true) > $dt) {
                $this->fail("Timeout waiting for lock remove");
            }
        }
    }

}
