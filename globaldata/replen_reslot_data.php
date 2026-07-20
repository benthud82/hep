<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['Login']) || $_SESSION['Login'] !== 'YES' || empty($_SESSION['MYUSER'])) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'Your session has expired. Sign in again.'));
    exit;
}

date_default_timezone_set('America/Chicago');

try {
    include_once __DIR__ . '/../connection/connection_details.php';
    include_once __DIR__ . '/../globalfunctions/replen_reslot_functions.php';

    $user = strtoupper(trim($_SESSION['MYUSER']));
    $warehouseStatement = $conn1->prepare("SELECT slottingDB_users_PRIMDC FROM hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = ?");
    $warehouseStatement->execute(array($user));
    $warehouse = $warehouseStatement->fetchColumn();
    if (strtoupper((string) $warehouse) !== 'HEP') {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'This module is currently limited to HEP users.'));
        exit;
    }

    $view = isset($_GET['view']) ? strtolower(trim($_GET['view'])) : 'summary';
    $allowedViews = array('summary', 'opportunities', 'plans', 'plan_detail');
    if (!in_array($view, $allowedViews, true)) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'message' => 'Unsupported replenishment planner view.'));
        exit;
    }

    $config = replen_config($conn1);
    $health = replen_model_health($conn1, $config);
    $response = array(
        'success' => true,
        'view' => $view,
        'read_at' => date('c'),
        'warehouse' => strtoupper($warehouse),
        'current_user' => $user,
        'csrf_token' => replen_csrf_token(),
        'config' => $config,
        'health' => $health
    );

    if ($view === 'summary' || $view === 'opportunities') {
        $opportunities = replen_build_opportunities($conn1, $config, $health);
        $response['summary'] = replen_opportunity_summary($opportunities, $config, $health);
        if ($view === 'opportunities') {
            $response['rows'] = $opportunities;
            $users = $conn1->query("SELECT idslottingDB_users_ID AS user_id,
                    CONCAT(idslottingDB_users_ID, ' | ', COALESCE(slottingDB_users_FIRSTNAME, ''), ' ', COALESCE(slottingDB_users_LASTNAME, '')) AS user_name
                FROM hep.slottingdb_users
                WHERE slottingDB_users_PRIMDC = 'HEP'
                ORDER BY idslottingDB_users_ID")->fetchAll(PDO::FETCH_ASSOC);
            $response['users'] = $users;
        }
    } elseif ($view === 'plans') {
        $response['plans'] = array();
        if (replen_table_exists($conn1, 'replen_reslot_plans')) {
            $statement = $conn1->prepare("SELECT
                    P.plan_id,
                    P.plan_name,
                    P.planned_date,
                    P.plan_status,
                    P.owner_user,
                    P.created_by,
                    P.model_as_of,
                    P.expected_replens_day,
                    P.expected_walk_meters_day,
                    P.expected_net_minutes_day,
                    P.expected_annual_labor_hours,
                    P.action_count,
                    P.notes,
                    P.created_at,
                    P.updated_at,
                    P.row_version,
                    SUM(CASE WHEN I.item_status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_actions,
                    SUM(CASE WHEN I.item_status = 'BLOCKED' THEN 1 ELSE 0 END) AS blocked_actions
                FROM hep.replen_reslot_plans P
                LEFT JOIN hep.replen_reslot_plan_items I ON I.plan_id = P.plan_id
                WHERE P.warehouse = ?
                GROUP BY P.plan_id
                ORDER BY FIELD(P.plan_status, 'IN_PROGRESS', 'READY', 'DRAFT', 'COMPLETED', 'CANCELLED'), P.planned_date DESC, P.plan_id DESC");
            $statement->execute(array(strtoupper($warehouse)));
            $response['plans'] = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($response['plans'] as &$planRow) {
                $planRow['can_manage'] = true;
                $planRow['can_delete'] = true;
            }
            unset($planRow);

            $users = $conn1->query("SELECT idslottingDB_users_ID AS user_id,
                    CONCAT(idslottingDB_users_ID, ' | ', COALESCE(slottingDB_users_FIRSTNAME, ''), ' ', COALESCE(slottingDB_users_LASTNAME, '')) AS user_name
                FROM hep.slottingdb_users
                WHERE slottingDB_users_PRIMDC = 'HEP'
                ORDER BY idslottingDB_users_ID")->fetchAll(PDO::FETCH_ASSOC);
            $response['users'] = $users;
        }
    } else {
        if (!replen_table_exists($conn1, 'replen_reslot_plans')) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'message' => 'Saved replenishment plans are not installed.'));
            exit;
        }
        $planId = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;
        if ($planId <= 0) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'message' => 'A valid plan_id is required.'));
            exit;
        }

        $planStatement = $conn1->prepare("SELECT * FROM hep.replen_reslot_plans WHERE plan_id = ? AND warehouse = ?");
        $planStatement->execute(array($planId, strtoupper($warehouse)));
        $plan = $planStatement->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'message' => 'The replenishment plan was not found.'));
            exit;
        }

        $itemStatement = $conn1->prepare("SELECT * FROM hep.replen_reslot_plan_items WHERE plan_id = ? ORDER BY sequence_no, plan_item_id");
        $itemStatement->execute(array($planId));
        $eventStatement = $conn1->prepare("SELECT * FROM hep.replen_reslot_plan_events WHERE plan_id = ? ORDER BY created_at DESC, event_id DESC LIMIT 200");
        $eventStatement->execute(array($planId));
        $isOwner = strtoupper($plan['owner_user']) === $user;

        $response['plan'] = $plan;
        $response['items'] = $itemStatement->fetchAll(PDO::FETCH_ASSOC);
        $response['events'] = $eventStatement->fetchAll(PDO::FETCH_ASSOC);
        $response['permissions'] = array(
            'can_manage' => true,
            'can_delete' => true,
            'can_manage_actions' => $isOwner,
            'current_user' => $user
        );
    }

    echo json_encode($response);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'The replenishment planner data is temporarily unavailable.'
    ));
}
