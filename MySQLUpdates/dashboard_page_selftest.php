<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

session_save_path(sys_get_temp_dir());
session_id('dashboard-page-selftest');
session_start();
$_SESSION['Login'] = 'YES';
$_SESSION['MYUSER'] = 'BENTHUD82';
session_write_close();

ob_start();
include __DIR__ . '/../dashboard.php';
$html = ob_get_clean();

$requiredMarkup = array(
    'SLOTTING CONTROL CENTER',
    'dashboard-conveyor-card',
    'dashboard-replen-card',
    'conveyor_reslot.php',
    'replen_reslot.php',
    'js/dashboard.js',
    'graph_fpp',
    'container_fpp',
    'graph_historicalscores',
    'container_scores',
    'graph_historicalreplens',
    'container_replens',
    'graph_capacity',
    'L04 Level A Cap',
    'js/dashboard_history.js'
);
$removedDetail = array(
    'Loose Slotting Statistics',
    'conveyor-dashboard-levels',
    'container_replens_actual',
    'container_replenstolines_actual',
    'container_shortstolines_actual'
);

foreach ($requiredMarkup as $needle) {
    if (strpos($html, $needle) === false) {
        fwrite(STDERR, 'Missing dashboard markup: ' . $needle . PHP_EOL);
        exit(1);
    }
}

foreach ($removedDetail as $needle) {
    if (strpos($html, $needle) !== false) {
        fwrite(STDERR, 'Detailed content remains on dashboard: ' . $needle . PHP_EOL);
        exit(1);
    }
}

echo 'Executive dashboard with historical analytics render passed. bytes=' . strlen($html) . PHP_EOL;
