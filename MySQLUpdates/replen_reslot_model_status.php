<?php

include_once __DIR__ . '/../connection/connection_details.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$requiredTables = array('my_npfmvc', 'optimalbay', 'slotmaster', 'item_location', 'replen_reslot_model_runs');
$missing = array();
foreach ($requiredTables as $tableName) {
    $statement = $conn1->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'hep' AND table_name = ?");
    $statement->execute(array($tableName));
    if ((int) $statement->fetchColumn() === 0) {
        $missing[] = $tableName;
    }
}

if (count($missing) > 0) {
    fwrite(STDERR, 'Cannot record replenishment planner refresh; missing tables: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

$counts = array();
foreach (array('my_npfmvc', 'optimalbay', 'slotmaster', 'item_location') as $tableName) {
    $counts[$tableName] = (int) $conn1->query("SELECT COUNT(*) FROM hep.{$tableName}")->fetchColumn();
}

$status = min($counts) > 0 ? 'SUCCESS' : 'FAILED';
$message = $status === 'SUCCESS' ? 'HEP slotting model refresh completed.' : 'One or more required model tables are empty.';
$statement = $conn1->prepare("INSERT INTO hep.replen_reslot_model_runs (
        status, started_at, finished_at, my_npfmvc_rows, optimalbay_rows, slotmaster_rows, item_location_rows, message
    ) VALUES (?, NULL, NOW(), ?, ?, ?, ?, ?)");
$statement->execute(array(
    $status,
    $counts['my_npfmvc'],
    $counts['optimalbay'],
    $counts['slotmaster'],
    $counts['item_location'],
    $message
));

echo $message . PHP_EOL;
exit($status === 'SUCCESS' ? 0 : 1);
