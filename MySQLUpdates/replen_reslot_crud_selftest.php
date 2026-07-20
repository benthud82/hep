<?php

if (php_sapi_name() !== 'cli') {
    exit(1);
}

$selfTestUser = 'BENTHUD82';
$selfTestToken = 'replen-reslot-crud-token';

if (isset($argv[1]) && $argv[1] === '--child') {
    session_save_path(sys_get_temp_dir());
    session_id($argv[2]);
    $_POST = json_decode(base64_decode($argv[3]), true);
    include __DIR__ . '/../formpost/replen_reslot_plan.php';
    exit(0);
}

function replen_crud_call_endpoint(array $payload, $user, $token, $suffix) {
    $sessionId = 'replen-reslot-crud-' . $suffix;
    session_save_path(sys_get_temp_dir());
    session_id($sessionId);
    session_start();
    $_SESSION = array(
        'Login' => 'YES',
        'MYUSER' => $user,
        'replen_reslot_csrf' => $token
    );
    session_write_close();

    $payload['csrf_token'] = $token;
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --child '
        . escapeshellarg($sessionId) . ' ' . escapeshellarg(base64_encode(json_encode($payload)));
    $output = array();
    $exitCode = 0;
    exec($command, $output, $exitCode);
    $response = json_decode(implode("\n", $output), true);
    if ($exitCode !== 0 || !is_array($response)) {
        throw new RuntimeException('Endpoint response was invalid: ' . implode("\n", $output));
    }
    return $response;
}

function replen_crud_fixture(PDO $conn, $name, $user) {
    $planStatement = $conn->prepare("INSERT INTO hep.replen_reslot_plans (
            plan_name, warehouse, planned_date, plan_status, owner_user, created_by,
            model_run_id, model_as_of, replens_per_hour, walk_meters_per_second, operating_days,
            expected_replens_day, expected_walk_meters_day, expected_net_minutes_day,
            expected_annual_labor_hours, action_count, notes, created_at, updated_at, row_version
        ) VALUES (?, 'HEP', CURDATE(), 'READY', ?, ?, NULL, NOW(), 15.00, 1.400, 253,
            1.0000, 0.000, 4.0000, 16.87, 1, 'Self-test fixture', NOW(), NOW(), 1)");
    $planStatement->execute(array($name, $user, $user));
    $planId = (int) $conn->lastInsertId();

    $itemNumber = 900000000 + ($planId % 99999999);
    $location = 'CRUD' . str_pad((string) ($planId % 99999999999), 11, '0', STR_PAD_LEFT);
    $itemStatement = $conn->prepare("INSERT INTO hep.replen_reslot_plan_items (
            plan_id, sequence_no, action_type, warehouse, item_number, package_unit, package_type,
            level_code, from_location, target_location, current_replens_day, suggested_replens_day,
            replen_reduction_day, walk_reduction_meters_day, net_minutes_day, annual_labor_hours,
            shipment_occurrences, days_since_sale, confidence_code, assigned_to, item_status,
            created_at, updated_at
        ) VALUES (?, 1, 'DIRECT_RESLOT', 'HEP', ?, 1, 'LSE', 'A', 'CRUDSOURCE', ?,
            2.000000, 1.000000, 1.000000, 0.000, 4.0000, 16.87, 20, 1, 'READY',
            'UNASSIGNED', 'READY', NOW(), NOW())");
    $itemStatement->execute(array($planId, $itemNumber, $location));
    $planItemId = (int) $conn->lastInsertId();

    $reservationStatement = $conn->prepare("INSERT INTO hep.replen_reslot_location_reservations (
        warehouse, target_location, plan_id, plan_item_id, reserved_at
    ) VALUES ('HEP', ?, ?, ?, NOW())");
    $reservationStatement->execute(array($location, $planId, $planItemId));

    $eventStatement = $conn->prepare("INSERT INTO hep.replen_reslot_plan_events (
        plan_id, plan_item_id, event_type, from_status, to_status, event_user, event_note, created_at
    ) VALUES (?, NULL, 'PLAN_CREATED', NULL, 'READY', ?, 'Self-test fixture created.', NOW())");
    $eventStatement->execute(array($planId, $user));
    return array('plan_id' => $planId, 'plan_item_id' => $planItemId, 'location' => $location);
}

function replen_crud_cleanup(PDO $conn, array $planIds) {
    foreach (array_unique($planIds) as $planId) {
        if ((int) $planId <= 0) {
            continue;
        }
        $conn->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_id = ?")->execute(array($planId));
        $conn->prepare("DELETE FROM hep.replen_reslot_plan_events WHERE plan_id = ?")->execute(array($planId));
        $conn->prepare("DELETE FROM hep.replen_reslot_plan_items WHERE plan_id = ?")->execute(array($planId));
        $conn->prepare("DELETE FROM hep.replen_reslot_plans WHERE plan_id = ?")->execute(array($planId));
    }
}

$fixturePlanIds = array();

try {
    include __DIR__ . '/../connection/connection_details.php';
    $userStatement = $conn1->prepare("SELECT COUNT(*) FROM hep.slottingdb_users
        WHERE UPPER(idslottingDB_users_ID) = ? AND slottingDB_users_PRIMDC = 'HEP'");
    $userStatement->execute(array($selfTestUser));
    if ((int) $userStatement->fetchColumn() !== 1) {
        throw new RuntimeException('The self-test HEP user is unavailable.');
    }

    $tag = '__CRUD SELFTEST ' . date('YmdHis') . ' ' . mt_rand(1000, 9999);
    $editFixture = replen_crud_fixture($conn1, $tag . ' EDIT__', $selfTestUser);
    $fixturePlanIds[] = $editFixture['plan_id'];
    $editResponse = replen_crud_call_endpoint(array(
        'action' => 'update_plan',
        'plan_id' => $editFixture['plan_id'],
        'row_version' => 1,
        'plan_name' => $tag . ' UPDATED__',
        'planned_date' => '2099-12-31',
        'plan_status' => 'IN_PROGRESS',
        'status_note' => 'Floor work started before the daily refresh.',
        'notes' => 'Updated through the CRUD endpoint self-test.',
        'reassign_remaining' => true,
        'assigned_to' => $selfTestUser
    ), $selfTestUser, $selfTestToken, 'edit');
    if (empty($editResponse['success'])) {
        throw new RuntimeException('Plan update failed: ' . json_encode($editResponse));
    }
    $updatedStatement = $conn1->prepare("SELECT plan_name, planned_date, plan_status, notes, row_version FROM hep.replen_reslot_plans WHERE plan_id = ?");
    $updatedStatement->execute(array($editFixture['plan_id']));
    $updatedPlan = $updatedStatement->fetch(PDO::FETCH_ASSOC);
    $assignedStatement = $conn1->prepare("SELECT assigned_to FROM hep.replen_reslot_plan_items WHERE plan_item_id = ?");
    $assignedStatement->execute(array($editFixture['plan_item_id']));
    $updatedAssignee = $assignedStatement->fetchColumn();
    if (!$updatedPlan || $updatedPlan['planned_date'] !== '2099-12-31' || $updatedPlan['plan_status'] !== 'IN_PROGRESS'
        || (int) $updatedPlan['row_version'] !== 2 || strtoupper($updatedAssignee) !== $selfTestUser) {
        throw new RuntimeException('Plan update did not persist the expected values.');
    }
    $staleResponse = replen_crud_call_endpoint(array(
        'action' => 'update_plan',
        'plan_id' => $editFixture['plan_id'],
        'row_version' => 1,
        'plan_name' => $tag . ' STALE__',
        'planned_date' => '2099-12-31',
        'notes' => 'This stale edit must not be saved.',
        'reassign_remaining' => false
    ), $selfTestUser, $selfTestToken, 'stale');
    if (!empty($staleResponse['success']) || stripos(isset($staleResponse['message']) ? $staleResponse['message'] : '', 'changed after') === false) {
        throw new RuntimeException('A stale plan update was not rejected.');
    }

    $closeResponse = replen_crud_call_endpoint(array(
        'action' => 'update_plan',
        'plan_id' => $editFixture['plan_id'],
        'row_version' => 2,
        'plan_name' => $tag . ' UPDATED__',
        'planned_date' => '2099-12-31',
        'plan_status' => 'COMPLETED',
        'status_note' => 'Manual close-out before the daily refresh.',
        'notes' => 'Updated through the CRUD endpoint self-test.',
        'reassign_remaining' => false
    ), $selfTestUser, $selfTestToken, 'close');
    if (empty($closeResponse['success'])) {
        throw new RuntimeException('Manual plan close-out failed: ' . json_encode($closeResponse));
    }
    $closedReservation = $conn1->prepare("SELECT COUNT(*) FROM hep.replen_reslot_location_reservations WHERE plan_id = ?");
    $closedReservation->execute(array($editFixture['plan_id']));
    if ((int) $closedReservation->fetchColumn() !== 0) {
        throw new RuntimeException('Manual plan close-out did not release reservations.');
    }

    $reopenResponse = replen_crud_call_endpoint(array(
        'action' => 'update_plan',
        'plan_id' => $editFixture['plan_id'],
        'row_version' => 3,
        'plan_name' => $tag . ' UPDATED__',
        'planned_date' => '2099-12-31',
        'plan_status' => 'READY',
        'status_note' => 'Manual reopen after floor confirmation.',
        'notes' => 'Updated through the CRUD endpoint self-test.',
        'reassign_remaining' => false
    ), $selfTestUser, $selfTestToken, 'reopen');
    if (empty($reopenResponse['success'])) {
        throw new RuntimeException('Manual plan reopen failed: ' . json_encode($reopenResponse));
    }
    $reopenedStatement = $conn1->prepare("SELECT P.plan_status, P.row_version,
            (SELECT COUNT(*) FROM hep.replen_reslot_location_reservations R WHERE R.plan_id = P.plan_id) AS reservation_count
        FROM hep.replen_reslot_plans P WHERE P.plan_id = ?");
    $reopenedStatement->execute(array($editFixture['plan_id']));
    $reopenedPlan = $reopenedStatement->fetch(PDO::FETCH_ASSOC);
    if (!$reopenedPlan || $reopenedPlan['plan_status'] !== 'READY'
        || (int) $reopenedPlan['row_version'] !== 4 || (int) $reopenedPlan['reservation_count'] !== 1) {
        throw new RuntimeException('Manual plan reopen did not restore the plan and its reservation.');
    }
    $missingReasonResponse = replen_crud_call_endpoint(array(
        'action' => 'update_plan',
        'plan_id' => $editFixture['plan_id'],
        'row_version' => 4,
        'plan_name' => $tag . ' UPDATED__',
        'planned_date' => '2099-12-31',
        'plan_status' => 'IN_PROGRESS',
        'notes' => 'Updated through the CRUD endpoint self-test.',
        'reassign_remaining' => false
    ), $selfTestUser, $selfTestToken, 'missing-reason');
    if (!empty($missingReasonResponse['success'])
        || stripos(isset($missingReasonResponse['message']) ? $missingReasonResponse['message'] : '', 'status-change reason') === false) {
        throw new RuntimeException('A manual status change without a reason was not rejected.');
    }

    $deleteFixture = replen_crud_fixture($conn1, $tag . ' DELETE__', $selfTestUser);
    $fixturePlanIds[] = $deleteFixture['plan_id'];
    $deleteResponse = replen_crud_call_endpoint(array(
        'action' => 'delete_plan',
        'plan_id' => $deleteFixture['plan_id'],
        'row_version' => 1
    ), $selfTestUser, $selfTestToken, 'delete');
    if (empty($deleteResponse['success'])) {
        throw new RuntimeException('Untouched plan deletion failed: ' . json_encode($deleteResponse));
    }
    foreach (array('replen_reslot_plans', 'replen_reslot_plan_items', 'replen_reslot_plan_events', 'replen_reslot_location_reservations') as $tableName) {
        $deletedStatement = $conn1->prepare("SELECT COUNT(*) FROM hep.{$tableName} WHERE plan_id = ?");
        $deletedStatement->execute(array($deleteFixture['plan_id']));
        if ((int) $deletedStatement->fetchColumn() !== 0) {
            throw new RuntimeException('Deleted plan rows remain in ' . $tableName . '.');
        }
    }

    $activeFixture = replen_crud_fixture($conn1, $tag . ' ACTIVE DELETE__', $selfTestUser);
    $fixturePlanIds[] = $activeFixture['plan_id'];
    $activityStatement = $conn1->prepare("INSERT INTO hep.replen_reslot_plan_events (
        plan_id, plan_item_id, event_type, from_status, to_status, event_user, event_note, created_at
    ) VALUES (?, ?, 'ACTION_STARTED', 'READY', 'IN_PROGRESS', ?, 'Protected self-test activity.', NOW())");
    $activityStatement->execute(array($activeFixture['plan_id'], $activeFixture['plan_item_id'], $selfTestUser));
    $activeDeleteResponse = replen_crud_call_endpoint(array(
        'action' => 'delete_plan',
        'plan_id' => $activeFixture['plan_id'],
        'row_version' => 1
    ), $selfTestUser, $selfTestToken, 'active-delete');
    if (empty($activeDeleteResponse['success'])) {
        throw new RuntimeException('A plan with floor activity could not be completely deleted: ' . json_encode($activeDeleteResponse));
    }
    $activeDeleteCheck = $conn1->prepare("SELECT COUNT(*) FROM hep.replen_reslot_plans WHERE plan_id = ?");
    $activeDeleteCheck->execute(array($activeFixture['plan_id']));
    if ((int) $activeDeleteCheck->fetchColumn() !== 0) {
        throw new RuntimeException('The active plan row remained after hard deletion.');
    }

    replen_crud_cleanup($conn1, $fixturePlanIds);
    echo "Replenishment saved-plan CRUD integration self-test passed.\n";
} catch (Exception $exception) {
    if (isset($conn1) && $conn1 instanceof PDO) {
        replen_crud_cleanup($conn1, $fixturePlanIds);
    }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
