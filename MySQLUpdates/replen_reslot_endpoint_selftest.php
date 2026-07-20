<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

$view = isset($argv[1]) ? $argv[1] : 'summary';
if (!in_array($view, array('summary', 'opportunities', 'plans'), true)) {
    fwrite(STDERR, "Unsupported smoke-test view.\n");
    exit(1);
}

session_save_path(sys_get_temp_dir());
session_id('replen-reslot-endpoint-' . $view);
session_start();
$_SESSION['Login'] = 'YES';
$_SESSION['MYUSER'] = 'BENTHUD82';
session_write_close();

$_GET['view'] = $view;
ob_start();
include __DIR__ . '/../globaldata/replen_reslot_data.php';
$raw = ob_get_clean();
$payload = json_decode($raw, true);

if (!is_array($payload) || empty($payload['success'])) {
    fwrite(STDERR, $raw . PHP_EOL);
    exit(1);
}

if ($view === 'summary') {
    echo json_encode(array(
        'success' => true,
        'planning_available' => $payload['health']['planning_available'],
        'occupancy_available' => $payload['health']['occupancy_available'],
        'opportunity_count' => $payload['summary']['opportunity_count'],
        'annual_replens_avoided' => $payload['summary']['annual_replens_avoided'],
        'annual_labor_hours' => $payload['summary']['annual_labor_hours']
    ), JSON_PRETTY_PRINT) . PHP_EOL;
} elseif ($view === 'opportunities') {
    $keys = array();
    foreach ($payload['rows'] as $row) {
        if (!isset($row['key'], $row['action_type'], $row['annual_labor_hours'], $row['planning_eligible'])) {
            fwrite(STDERR, "Opportunity contract is incomplete.\n");
            exit(1);
        }
        $keys[] = $row['key'];
    }
    if (count($keys) !== count(array_unique($keys))) {
        fwrite(STDERR, "Opportunity natural keys are not unique.\n");
        exit(1);
    }
    echo json_encode(array(
        'success' => true,
        'row_count' => count($payload['rows']),
        'user_count' => count($payload['users']),
        'unique_key_count' => count(array_unique($keys))
    ), JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    if (!isset($payload['users']) || !is_array($payload['users'])) {
        fwrite(STDERR, "Saved-plan assignee contract is incomplete.\n");
        exit(1);
    }
    foreach ($payload['plans'] as $plan) {
        foreach (array('plan_id', 'plan_name', 'plan_status', 'row_version', 'can_manage', 'can_delete') as $field) {
            if (!array_key_exists($field, $plan)) {
                fwrite(STDERR, 'Saved-plan contract is missing ' . $field . ".\n");
                exit(1);
            }
        }
    }
    echo json_encode(array('success' => true, 'plan_count' => count($payload['plans'])), JSON_PRETTY_PRINT) . PHP_EOL;
}
