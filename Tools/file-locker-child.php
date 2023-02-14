<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

if (!isset($argv[1])) {
    fwrite(STDERR, "Arg #1 - file name not given or file does not exist\n");
    fwrite(STDOUT, "error:Arg #1 - file name not given or file does not exist");
    exit();
}
if (!isset($argv[2]) ||
        !is_numeric($argv[2]) ||
        (0 > (int) $argv[2]) ||
        (500 < (int) $argv[2])) {
    fwrite(STDERR, "Arg #2 - delay is not given, wrong type or not in 0..500 range\n");
    fwrite(STDOUT, "error:Arg #2 - delay is not given, wrong type or not in 0..500 range");
    exit();
}
if (!@touch($argv[1])) {
    fwrite(STDERR, microtime() . " Failed to create lock file: {$argv[1]}\n");
    fwrite(STDOUT, "error: can't create lock file");
    exit();
}
fwrite(STDERR, microtime(true) . " Lock file created: {$argv[1]}\n");
fwrite(STDOUT, "locked\n");
usleep($argv[2] * 1000);
@unlink($argv[1]);
fwrite(STDERR, microtime(true) . " Lock file removed: {$argv[1]}\n");
exit();
