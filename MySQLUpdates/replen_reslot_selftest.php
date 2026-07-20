<?php

include_once __DIR__ . '/../globalfunctions/replen_reslot_functions.php';

$failures = replen_self_test();
if (count($failures) > 0) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . PHP_EOL);
    }
    exit(1);
}

echo "Replenishment reslot helper self-test passed." . PHP_EOL;
