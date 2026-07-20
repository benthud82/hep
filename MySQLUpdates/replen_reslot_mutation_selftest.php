<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

$sessionId = 'replen-reslot-mutation-selftest';
$csrfToken = 'replen-reslot-mutation-token';

if (isset($argv[1]) && $argv[1] === '--child') {
    session_save_path(sys_get_temp_dir());
    session_id($sessionId);
    $_POST = array(
        'action' => 'create_plan',
        'csrf_token' => 'intentionally-invalid-token',
        'plan_name' => 'Guard test',
        'planned_date' => date('Y-m-d'),
        'assigned_to' => 'BENTHUD82',
        'opportunity_keys' => array('HEP|1|1|101|A')
    );
    include __DIR__ . '/../formpost/replen_reslot_plan.php';
    exit(0);
}

session_save_path(sys_get_temp_dir());
session_id($sessionId);
session_start();
$_SESSION['Login'] = 'YES';
$_SESSION['MYUSER'] = 'BENTHUD82';
$_SESSION['replen_reslot_csrf'] = $csrfToken;
session_write_close();

$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --child';
$output = array();
$exitCode = 0;
exec($command, $output, $exitCode);
$payload = json_decode(implode("\n", $output), true);

if (!is_array($payload) || !empty($payload['success'])
    || stripos(isset($payload['message']) ? $payload['message'] : '', 'security token is invalid') === false) {
    fwrite(STDERR, implode("\n", $output) . PHP_EOL);
    exit(1);
}

echo "Replenishment reslot CSRF mutation guard self-test passed.\n";
