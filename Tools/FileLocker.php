<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

/**
 * Class to hanlde lock and unlock files
 *
 */
class FileLocker {

    const LOG_FILE = __DIR__ . "/../Test/temp/FileLocker.log";
    const CHILD = __DIR__ . '/file-locker-child.php';

    protected $fileName;
    protected $processes = [];
    protected $pipesets = [];

    public function __construct(string $fileName) {
        $this->fileName = $fileName;
    }

    public function __destruct() {
        foreach ($this->processes as $process) {
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }
        }
        array_walk_recursive($this->pipesets, function ($val) {
            if (is_resource($val)) {
                fclose($val);
            } else {
                
            }
        });
        if (file_exists($this->fileName)) {
            unlink($this->fileName);
        }
    }

    /**
     * Put a file on disk and keep it on for time (ms)
     * 
     * @param int $time
     */
    public function lock(int $time) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("file", self::LOG_FILE, "a")
        );
        $pipeIndex = count($this->pipesets);
        $this->processes[] = proc_open(
                'php ' . self::CHILD . ' "' . $this->fileName . '" ' . $time,
                $descriptorspec,
                $this->pipesets[$pipeIndex]
        );
        $result = trim(fgets($this->pipesets[$pipeIndex][1]));
        if ($result != 'locked') {
            throw new \Exception("FileLocker: $result");
        }
        return true;
    }

    public function check() {
        return file_exists($this->fileName);
    }

}
