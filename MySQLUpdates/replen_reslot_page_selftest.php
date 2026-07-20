<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

session_save_path(sys_get_temp_dir());
session_id('replen-reslot-page-selftest');
session_start();
$_SESSION['Login'] = 'YES';
$_SESSION['MYUSER'] = 'BENTHUD82';
session_write_close();

ob_start();
include __DIR__ . '/../replen_reslot.php';
$html = ob_get_clean();

$requiredMarkup = array(
    'Replenishment Reslot Planner',
    'replen-health-alert',
    'data-replen-queue="physical"',
    'data-replen-queue="adjustments"',
    'replen-opportunity-table',
        'replen-plan-modal',
        'replen-plan-search',
        'replen-plan-status-filter',
        'replen-edit-plan-modal',
        'replen-edit-plan-status',
        'replen-detail-modal',
    'js/replen_reslot.js'
);

foreach ($requiredMarkup as $needle) {
    if (strpos($html, $needle) === false) {
        fwrite(STDERR, 'Missing page markup: ' . $needle . PHP_EOL);
        exit(1);
    }
}

echo 'Authenticated replenishment planner page render passed. bytes=' . strlen($html) . PHP_EOL;
