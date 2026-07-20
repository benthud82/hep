<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
date_default_timezone_set('America/Chicago');

function replen_plan_response($status, array $payload) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function replen_plan_event(PDO $conn, $planId, $planItemId, $eventType, $fromStatus, $toStatus, $user, $note) {
    $statement = $conn->prepare("INSERT INTO hep.replen_reslot_plan_events (
            plan_id, plan_item_id, event_type, from_status, to_status, event_user, event_note, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $statement->execute(array($planId, $planItemId, $eventType, $fromStatus, $toStatus, $user, $note));
}

function replen_plan_user_exists(PDO $conn, $user) {
    $statement = $conn->prepare("SELECT COUNT(*) FROM hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = ? AND slottingDB_users_PRIMDC = 'HEP'");
    $statement->execute(array(strtoupper($user)));
    return (int) $statement->fetchColumn() > 0;
}

function replen_plan_load_item(PDO $conn, $planItemId) {
    $statement = $conn->prepare("SELECT I.*, P.owner_user, P.plan_status
        FROM hep.replen_reslot_plan_items I
        JOIN hep.replen_reslot_plans P ON P.plan_id = I.plan_id
        WHERE I.plan_item_id = ?
        FOR UPDATE");
    $statement->execute(array($planItemId));
    return $statement->fetch(PDO::FETCH_ASSOC);
}

function replen_plan_can_update(array $item, $user) {
    return strtoupper($item['owner_user']) === strtoupper($user)
        || (!empty($item['assigned_to']) && strtoupper($item['assigned_to']) === strtoupper($user));
}

function replen_plan_refresh_status(PDO $conn, $planId) {
    $statement = $conn->prepare("SELECT
            SUM(item_status IN ('READY', 'IN_PROGRESS', 'BLOCKED')) AS active_count,
            SUM(item_status = 'IN_PROGRESS') AS in_progress_count,
            SUM(item_status = 'COMPLETED') AS completed_count,
            COUNT(*) AS total_count
        FROM hep.replen_reslot_plan_items
        WHERE plan_id = ?");
    $statement->execute(array($planId));
    $counts = $statement->fetch(PDO::FETCH_ASSOC);

    if ((int) $counts['total_count'] > 0 && (int) $counts['active_count'] === 0) {
        $status = 'COMPLETED';
        $completedSql = ', completed_at = NOW()';
    } elseif ((int) $counts['in_progress_count'] > 0 || (int) $counts['completed_count'] > 0) {
        $status = 'IN_PROGRESS';
        $completedSql = '';
    } else {
        $status = 'READY';
        $completedSql = '';
    }

    $update = $conn->prepare("UPDATE hep.replen_reslot_plans SET plan_status = ?, updated_at = NOW(), row_version = row_version + 1 {$completedSql} WHERE plan_id = ? AND plan_status <> 'CANCELLED'");
    $update->execute(array($status, $planId));
    return $status;
}

function replen_plan_block_dependents(PDO $conn, $planId, $planItemId, $user, $reason) {
    $queue = array((int) $planItemId);
    $seen = array();
    while (count($queue) > 0) {
        $parentId = array_shift($queue);
        if (isset($seen[$parentId])) {
            continue;
        }
        $seen[$parentId] = true;
        $statement = $conn->prepare("SELECT plan_item_id, item_status FROM hep.replen_reslot_plan_items
            WHERE plan_id = ? AND dependency_item_id = ? AND item_status IN ('READY', 'IN_PROGRESS', 'BLOCKED')");
        $statement->execute(array($planId, $parentId));
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $dependent) {
            $dependentId = (int) $dependent['plan_item_id'];
            $update = $conn->prepare("UPDATE hep.replen_reslot_plan_items
                SET item_status = 'BLOCKED', execution_note = ?, updated_at = NOW()
                WHERE plan_item_id = ?");
            $update->execute(array($reason, $dependentId));
            $deleteReservation = $conn->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_item_id = ?");
            $deleteReservation->execute(array($dependentId));
            replen_plan_event($conn, $planId, $dependentId, 'DEPENDENCY_BLOCKED', $dependent['item_status'], 'BLOCKED', $user, $reason);
            $queue[] = $dependentId;
        }
    }
}

function replen_plan_eligible_slots(PDO $conn, $warehouse) {
    $statement = $conn->prepare("SELECT
            S.slotmaster_loc AS location_code,
            S.slotmaster_level AS level_code,
            S.slotmaster_tier AS tier_code,
            S.slotmaster_dimgroup AS grid_code,
            CAST(S.slotmaster_grdeep AS UNSIGNED) AS depth_value,
            S.slotmaster_distance AS walk_distance,
            I.loc_item AS occupied_item,
            R.plan_id AS reserved_plan_id
        FROM hep.slotmaster S
        LEFT JOIN hep.item_location I ON I.loc_location = S.slotmaster_loc
        LEFT JOIN hep.replen_reslot_location_reservations R
            ON R.warehouse = S.slotmaster_branch AND R.target_location = S.slotmaster_loc
        WHERE S.slotmaster_branch = ?
            AND COALESCE(S.slotmaster_block, '') = ''
            AND S.slotmaster_locdesc NOT LIKE 'GS%'
            AND S.slotmaster_locdesc NOT LIKE 'WK%'
            AND S.slotmaster_locdesc NOT LIKE 'VS%'
            AND S.slotmaster_locdesc NOT LIKE 'KH%'
        ORDER BY S.slotmaster_loc
        FOR UPDATE");
    $statement->execute(array($warehouse));
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function replen_plan_best_slot(array $slots, $optimalDistance, array $usedTargets, $vacantOnly, array $selectedByFrom, array $assigned) {
    $best = null;
    $bestDistance = null;
    foreach ($slots as $slot) {
        $location = $slot['location_code'];
        if (isset($usedTargets[$location]) || !empty($slot['reserved_plan_id'])) {
            continue;
        }
        $occupied = $slot['occupied_item'] !== null && $slot['occupied_item'] !== '';
        if ($vacantOnly && $occupied) {
            continue;
        }
        if (!$vacantOnly) {
            if (!$occupied || !isset($selectedByFrom[$location])) {
                continue;
            }
            $occupantKey = $selectedByFrom[$location];
            if (!isset($assigned[$occupantKey])) {
                continue;
            }
        }
        $distance = abs((int) $slot['walk_distance'] - (int) $optimalDistance);
        if ($best === null || $distance < $bestDistance || ($distance === $bestDistance && strcmp($location, $best['location_code']) < 0)) {
            $best = $slot;
            $bestDistance = $distance;
        }
    }
    return $best;
}

function replen_plan_dependency_depth($key, array $assigned, array &$memo, array &$stack) {
    if (isset($memo[$key])) {
        return $memo[$key];
    }
    if (isset($stack[$key])) {
        throw new Exception('A circular move dependency was detected.');
    }
    $stack[$key] = true;
    $dependencyKey = isset($assigned[$key]['dependency_key']) ? $assigned[$key]['dependency_key'] : null;
    $depth = $dependencyKey ? replen_plan_dependency_depth($dependencyKey, $assigned, $memo, $stack) + 1 : 1;
    unset($stack[$key]);
    $memo[$key] = $depth;
    return $depth;
}

if (!isset($_SESSION['Login']) || $_SESSION['Login'] !== 'YES' || empty($_SESSION['MYUSER'])) {
    replen_plan_response(401, array('success' => false, 'message' => 'Your session has expired. Sign in again.'));
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

if (empty($input['csrf_token']) || empty($_SESSION['replen_reslot_csrf'])
    || !hash_equals($_SESSION['replen_reslot_csrf'], (string) $input['csrf_token'])) {
    replen_plan_response(403, array('success' => false, 'message' => 'The security token is invalid. Refresh the page and try again.'));
}

try {
    include_once __DIR__ . '/../connection/connection_details.php';
    include_once __DIR__ . '/../globalfunctions/replen_reslot_functions.php';

    foreach (array('replen_reslot_plans', 'replen_reslot_plan_items', 'replen_reslot_location_reservations', 'replen_reslot_plan_events') as $requiredTable) {
        if (!replen_table_exists($conn1, $requiredTable)) {
            replen_plan_response(503, array('success' => false, 'message' => 'The replenishment planner schema has not been installed.'));
        }
    }

    $user = strtoupper(trim($_SESSION['MYUSER']));
    $warehouseStatement = $conn1->prepare("SELECT slottingDB_users_PRIMDC FROM hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = ?");
    $warehouseStatement->execute(array($user));
    $warehouse = strtoupper((string) $warehouseStatement->fetchColumn());
    if ($warehouse !== 'HEP') {
        replen_plan_response(403, array('success' => false, 'message' => 'This module is currently limited to HEP users.'));
    }

    $action = isset($input['action']) ? strtolower(trim($input['action'])) : '';
    $allowedActions = array('create_plan', 'update_plan', 'delete_plan', 'assign_action', 'start_action', 'complete_action', 'block_action', 'skip_action', 'cancel_plan');
    if (!in_array($action, $allowedActions, true)) {
        replen_plan_response(400, array('success' => false, 'message' => 'Unsupported plan action.'));
    }

    if ($action === 'create_plan') {
        $config = replen_config($conn1);
        $health = replen_model_health($conn1, $config);
        if (!$health['planning_available']) {
            replen_plan_response(409, array('success' => false, 'message' => $health['message']));
        }

        $planName = isset($input['plan_name']) ? trim($input['plan_name']) : '';
        $plannedDate = isset($input['planned_date']) ? trim($input['planned_date']) : '';
        $assignedTo = isset($input['assigned_to']) ? strtoupper(trim($input['assigned_to'])) : $user;
        $notes = isset($input['notes']) ? trim($input['notes']) : '';
        $selectedKeys = isset($input['opportunity_keys']) && is_array($input['opportunity_keys']) ? array_values(array_unique($input['opportunity_keys'])) : array();
        $overrideReasons = isset($input['override_reasons']) && is_array($input['override_reasons']) ? $input['override_reasons'] : array();

        if ($planName === '' || strlen($planName) > 100) {
            replen_plan_response(400, array('success' => false, 'message' => 'Enter a plan name of 100 characters or fewer.'));
        }
        if (strlen($notes) > 2000) {
            replen_plan_response(400, array('success' => false, 'message' => 'Plan notes must be 2,000 characters or fewer.'));
        }
        $dateObject = DateTime::createFromFormat('Y-m-d', $plannedDate);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $plannedDate) {
            replen_plan_response(400, array('success' => false, 'message' => 'Enter a valid planned date.'));
        }
        if (count($selectedKeys) < 1 || count($selectedKeys) > 250) {
            replen_plan_response(400, array('success' => false, 'message' => 'Select between 1 and 250 opportunities for one floor plan.'));
        }
        if (!replen_plan_user_exists($conn1, $assignedTo)) {
            replen_plan_response(400, array('success' => false, 'message' => 'The selected assignee is not an active HEP user.'));
        }

        $allOpportunities = replen_build_opportunities($conn1, $config, $health);
        $opportunityMap = array();
        foreach ($allOpportunities as $opportunity) {
            $opportunityMap[$opportunity['key']] = $opportunity;
        }

        $selected = array();
        $selectedByFrom = array();
        foreach ($selectedKeys as $key) {
            if (!isset($opportunityMap[$key])) {
                replen_plan_response(409, array('success' => false, 'message' => 'One or more selected recommendations are no longer available. Refresh the opportunity list.'));
            }
            $row = $opportunityMap[$key];
            $override = isset($overrideReasons[$key]) ? trim($overrideReasons[$key]) : '';
            $overrideAllowed = !empty($row['override_allowed']);
            if (!$row['planning_eligible'] && !($overrideAllowed && $override !== '')) {
                replen_plan_response(409, array('success' => false, 'message' => 'Item ' . $row['item_number'] . ' requires review and a permitted override before planning.'));
            }
            if ($row['action_type'] === 'REVIEW_REQUIRED') {
                replen_plan_response(409, array('success' => false, 'message' => 'Item ' . $row['item_number'] . ' has no feasible target profile.'));
            }
            $row['override_reason'] = $override;
            $selected[$key] = $row;
            if ($row['action_type'] !== 'MAX_MIN_ADJUSTMENT') {
                if (isset($selectedByFrom[$row['current_location']])) {
                    replen_plan_response(409, array('success' => false, 'message' => 'Two selected model rows refer to the same current location: ' . $row['current_location'] . '. Select only one.'));
                }
                $selectedByFrom[$row['current_location']] = $key;
            }
        }

        $conn1->beginTransaction();
        $slots = replen_plan_eligible_slots($conn1, $warehouse);
        $slotsByProfile = array();
        foreach ($slots as $slot) {
            $profileKey = replen_profile_key($slot['level_code'], $slot['tier_code'], $slot['grid_code'], $slot['depth_value']);
            if (!isset($slotsByProfile[$profileKey])) {
                $slotsByProfile[$profileKey] = array();
            }
            $slotsByProfile[$profileKey][] = $slot;
        }

        $candidateKeys = array_keys($selected);
        usort($candidateKeys, function ($leftKey, $rightKey) use ($selected, $slotsByProfile) {
            $left = $selected[$leftKey];
            $right = $selected[$rightKey];
            $leftProfile = replen_profile_key($left['level'], $left['suggested_tier'], $left['suggested_grid'], $left['suggested_depth']);
            $rightProfile = replen_profile_key($right['level'], $right['suggested_tier'], $right['suggested_grid'], $right['suggested_depth']);
            $leftCount = isset($slotsByProfile[$leftProfile]) ? count($slotsByProfile[$leftProfile]) : PHP_INT_MAX;
            $rightCount = isset($slotsByProfile[$rightProfile]) ? count($slotsByProfile[$rightProfile]) : PHP_INT_MAX;
            if ($leftCount !== $rightCount) {
                return $leftCount - $rightCount;
            }
            return $left['annual_labor_hours'] > $right['annual_labor_hours'] ? -1 : 1;
        });

        $assigned = array();
        $usedTargets = array();
        $unresolved = array();
        foreach ($candidateKeys as $key) {
            $row = $selected[$key];
            if ($row['action_type'] === 'MAX_MIN_ADJUSTMENT') {
                $assigned[$key] = array('target_location' => $row['current_location'], 'dependency_key' => null, 'action_type' => 'MAX_MIN_ADJUSTMENT');
                continue;
            }
            $profileKey = replen_profile_key($row['level'], $row['suggested_tier'], $row['suggested_grid'], $row['suggested_depth']);
            $profileSlots = isset($slotsByProfile[$profileKey]) ? $slotsByProfile[$profileKey] : array();
            $slot = replen_plan_best_slot($profileSlots, $row['optimal_walk_distance'], $usedTargets, true, $selectedByFrom, $assigned);
            if ($slot) {
                $assigned[$key] = array('target_location' => $slot['location_code'], 'dependency_key' => null, 'action_type' => 'DIRECT_RESLOT');
                $usedTargets[$slot['location_code']] = true;
            } else {
                $unresolved[$key] = true;
            }
        }

        $madeProgress = true;
        while (count($unresolved) > 0 && $madeProgress) {
            $madeProgress = false;
            foreach (array_keys($unresolved) as $key) {
                $row = $selected[$key];
                $profileKey = replen_profile_key($row['level'], $row['suggested_tier'], $row['suggested_grid'], $row['suggested_depth']);
                $profileSlots = isset($slotsByProfile[$profileKey]) ? $slotsByProfile[$profileKey] : array();
                $slot = replen_plan_best_slot($profileSlots, $row['optimal_walk_distance'], $usedTargets, false, $selectedByFrom, $assigned);
                if ($slot) {
                    $dependencyKey = $selectedByFrom[$slot['location_code']];
                    if ($dependencyKey !== $key) {
                        $assigned[$key] = array('target_location' => $slot['location_code'], 'dependency_key' => $dependencyKey, 'action_type' => 'DEPENDENCY_RESLOT');
                        $usedTargets[$slot['location_code']] = true;
                        unset($unresolved[$key]);
                        $madeProgress = true;
                    }
                }
            }
        }

        if (count($unresolved) > 0) {
            $blockedItems = array();
            foreach (array_keys($unresolved) as $key) {
                $blockedItems[] = $selected[$key]['item_number'];
            }
            $conn1->rollBack();
            replen_plan_response(409, array(
                'success' => false,
                'message' => 'Exact locations could not be assigned for all selected items. Add enabling moves or remove the blocked items.',
                'blocked_items' => $blockedItems
            ));
        }

        $totals = array('replens' => 0, 'walk' => 0, 'minutes' => 0, 'hours' => 0);
        foreach ($selected as $row) {
            $totals['replens'] += $row['replen_reduction_day'];
            $totals['walk'] += $row['walk_reduction_meters_day'];
            $totals['minutes'] += $row['net_minutes_day'];
            $totals['hours'] += $row['annual_labor_hours'];
        }

        $planInsert = $conn1->prepare("INSERT INTO hep.replen_reslot_plans (
                plan_name, warehouse, planned_date, plan_status, owner_user, created_by,
                model_run_id, model_as_of, replens_per_hour, walk_meters_per_second, operating_days,
                expected_replens_day, expected_walk_meters_day, expected_net_minutes_day,
                expected_annual_labor_hours, action_count, notes, created_at, updated_at
            ) VALUES (?, ?, ?, 'READY', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $planInsert->execute(array(
            $planName, $warehouse, $plannedDate, $user, $user,
            $health['model_run_id'], $health['model_as_of'], $config['replens_per_hour'],
            $config['walk_meters_per_second'], $config['operating_days'], $totals['replens'],
            $totals['walk'], $totals['minutes'], $totals['hours'], count($selected), $notes
        ));
        $planId = (int) $conn1->lastInsertId();

        $depthMemo = array();
        $depthStack = array();
        foreach (array_keys($assigned) as $key) {
            replen_plan_dependency_depth($key, $assigned, $depthMemo, $depthStack);
        }
        $orderedKeys = array_keys($selected);
        usort($orderedKeys, function ($leftKey, $rightKey) use ($selected, $depthMemo) {
            if ($depthMemo[$leftKey] !== $depthMemo[$rightKey]) {
                return $depthMemo[$leftKey] - $depthMemo[$rightKey];
            }
            if ($selected[$leftKey]['annual_labor_hours'] != $selected[$rightKey]['annual_labor_hours']) {
                return $selected[$leftKey]['annual_labor_hours'] > $selected[$rightKey]['annual_labor_hours'] ? -1 : 1;
            }
            return strcmp($leftKey, $rightKey);
        });

        $itemInsert = $conn1->prepare("INSERT INTO hep.replen_reslot_plan_items (
                plan_id, sequence_no, dependency_item_id, action_type, warehouse, item_number,
                package_unit, package_type, level_code, from_location, target_location,
                current_tier, current_grid, current_depth, suggested_tier, suggested_grid,
                suggested_depth, current_max, current_min, suggested_max, suggested_min,
                current_replens_day, suggested_replens_day, replen_reduction_day,
                walk_reduction_meters_day, net_minutes_day, annual_labor_hours,
                shipment_occurrences, days_since_sale, confidence_code, override_reason,
                assigned_to, item_status, created_at, updated_at
            ) VALUES (
                ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'READY', NOW(), NOW()
            )");
        $planItemIds = array();
        foreach ($orderedKeys as $sequenceIndex => $key) {
            $row = $selected[$key];
            $assignment = $assigned[$key];
            $itemInsert->execute(array(
                $planId, $sequenceIndex + 1, $assignment['action_type'], $warehouse,
                $row['item_number'], $row['package_unit'], $row['package_type'], $row['level'],
                $row['current_location'], $assignment['target_location'], $row['current_tier'],
                $row['current_grid'], $row['current_depth'], $row['suggested_tier'],
                $row['suggested_grid'], $row['suggested_depth'], $row['current_max'],
                $row['current_min'], $row['suggested_max'], $row['suggested_min'],
                $row['current_replens_day'], $row['suggested_replens_day'],
                $row['replen_reduction_day'], $row['walk_reduction_meters_day'],
                $row['net_minutes_day'], $row['annual_labor_hours'], $row['shipment_occurrences'],
                $row['days_since_sale'], $row['confidence'], $row['override_reason'], $assignedTo
            ));
            $planItemIds[$key] = (int) $conn1->lastInsertId();
        }

        $dependencyUpdate = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET dependency_item_id = ? WHERE plan_item_id = ?");
        $reservationInsert = $conn1->prepare("INSERT INTO hep.replen_reslot_location_reservations (
                warehouse, target_location, plan_id, plan_item_id, reserved_at
            ) VALUES (?, ?, ?, ?, NOW())");
        foreach ($orderedKeys as $key) {
            $assignment = $assigned[$key];
            $planItemId = $planItemIds[$key];
            if ($assignment['dependency_key']) {
                $dependencyUpdate->execute(array($planItemIds[$assignment['dependency_key']], $planItemId));
            }
            if ($assignment['action_type'] !== 'MAX_MIN_ADJUSTMENT') {
                $reservationInsert->execute(array($warehouse, $assignment['target_location'], $planId, $planItemId));
            }
        }

        replen_plan_event($conn1, $planId, null, 'PLAN_CREATED', null, 'READY', $user, 'Created with ' . count($selected) . ' planned actions.');
        $conn1->commit();
        replen_plan_response(201, array('success' => true, 'message' => 'The replenishment floor plan was created.', 'plan_id' => $planId));
    }

    $conn1->beginTransaction();

    if ($action === 'update_plan' || $action === 'delete_plan') {
        $planId = isset($input['plan_id']) ? (int) $input['plan_id'] : 0;
        $rowVersion = isset($input['row_version']) ? (int) $input['row_version'] : 0;
        $statement = $conn1->prepare("SELECT * FROM hep.replen_reslot_plans WHERE plan_id = ? AND warehouse = ? FOR UPDATE");
        $statement->execute(array($planId, $warehouse));
        $plan = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            $conn1->rollBack();
            replen_plan_response(404, array('success' => false, 'message' => 'Plan not found.'));
        }
        if ($rowVersion <= 0 || (int) $plan['row_version'] !== $rowVersion) {
            $conn1->rollBack();
            replen_plan_response(409, array('success' => false, 'message' => 'This plan changed after you opened it. Refresh the plan before saving.'));
        }

        if ($action === 'delete_plan') {
            $deleteReservations = $conn1->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_id = ?");
            $deleteReservations->execute(array($planId));
            $deleteEvents = $conn1->prepare("DELETE FROM hep.replen_reslot_plan_events WHERE plan_id = ?");
            $deleteEvents->execute(array($planId));
            $deleteItems = $conn1->prepare("DELETE FROM hep.replen_reslot_plan_items WHERE plan_id = ?");
            $deleteItems->execute(array($planId));
            $deletePlan = $conn1->prepare("DELETE FROM hep.replen_reslot_plans WHERE plan_id = ?");
            $deletePlan->execute(array($planId));
            $conn1->commit();
            replen_plan_response(200, array('success' => true, 'message' => 'The saved plan and all of its action history were permanently deleted.'));
        }

        $planName = isset($input['plan_name']) ? trim($input['plan_name']) : '';
        $plannedDate = isset($input['planned_date']) ? trim($input['planned_date']) : '';
        $planStatus = isset($input['plan_status']) ? strtoupper(trim($input['plan_status'])) : $plan['plan_status'];
        $statusNote = isset($input['status_note']) ? trim($input['status_note']) : '';
        $notes = isset($input['notes']) ? trim($input['notes']) : '';
        $reassignRemaining = !empty($input['reassign_remaining']);
        $assignedTo = isset($input['assigned_to']) ? strtoupper(trim($input['assigned_to'])) : '';
        if ($planName === '' || strlen($planName) > 100) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Enter a plan name of 100 characters or fewer.'));
        }
        if (strlen($notes) > 2000) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Plan notes must be 2,000 characters or fewer.'));
        }
        if (!in_array($planStatus, array('READY', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'), true)) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Select a valid plan status.'));
        }
        if ($planStatus !== $plan['plan_status'] && ($statusNote === '' || strlen($statusNote) > 1000)) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Enter a status-change reason of 1,000 characters or fewer.'));
        }
        $dateObject = DateTime::createFromFormat('Y-m-d', $plannedDate);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $plannedDate) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Enter a valid planned date.'));
        }

        $changes = array();
        if ($planName !== $plan['plan_name']) {
            $changes[] = 'Renamed from "' . $plan['plan_name'] . '" to "' . $planName . '".';
        }
        if ($plannedDate !== $plan['planned_date']) {
            $changes[] = 'Planned date changed from ' . $plan['planned_date'] . ' to ' . $plannedDate . '.';
        }
        if ($notes !== trim((string) $plan['notes'])) {
            $changes[] = 'Plan notes updated.';
        }
        if ($planStatus !== $plan['plan_status']) {
            $changes[] = 'Status changed from ' . $plan['plan_status'] . ' to ' . $planStatus . '. Reason: ' . $statusNote;

            if (in_array($planStatus, array('COMPLETED', 'CANCELLED'), true)) {
                $releaseReservations = $conn1->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_id = ?");
                $releaseReservations->execute(array($planId));
            } elseif (in_array($plan['plan_status'], array('COMPLETED', 'CANCELLED'), true)) {
                $outstandingStatement = $conn1->prepare("SELECT plan_item_id, action_type, target_location
                    FROM hep.replen_reslot_plan_items
                    WHERE plan_id = ? AND item_status IN ('READY', 'IN_PROGRESS', 'BLOCKED')
                    ORDER BY sequence_no, plan_item_id");
                $outstandingStatement->execute(array($planId));
                $reservationCheck = $conn1->prepare("SELECT plan_id FROM hep.replen_reslot_location_reservations
                    WHERE warehouse = ? AND target_location = ? FOR UPDATE");
                $reservationInsert = $conn1->prepare("INSERT INTO hep.replen_reslot_location_reservations (
                    warehouse, target_location, plan_id, plan_item_id, reserved_at
                ) VALUES (?, ?, ?, ?, NOW())");
                foreach ($outstandingStatement->fetchAll(PDO::FETCH_ASSOC) as $outstandingItem) {
                    if ($outstandingItem['action_type'] === 'MAX_MIN_ADJUSTMENT') {
                        continue;
                    }
                    $targetLocation = strtoupper(trim((string) $outstandingItem['target_location']));
                    if ($targetLocation === '') {
                        $conn1->rollBack();
                        replen_plan_response(409, array('success' => false, 'message' => 'This plan cannot be reopened because an unfinished action has no target location.'));
                    }
                    $reservationCheck->execute(array($warehouse, $targetLocation));
                    $reservedPlanId = $reservationCheck->fetchColumn();
                    if ($reservedPlanId !== false && (int) $reservedPlanId !== $planId) {
                        $conn1->rollBack();
                        replen_plan_response(409, array('success' => false, 'message' => 'This plan cannot be reopened because target ' . $targetLocation . ' is reserved by another saved plan.'));
                    }
                    if ($reservedPlanId === false) {
                        $reservationInsert->execute(array($warehouse, $targetLocation, $planId, $outstandingItem['plan_item_id']));
                    }
                }
            }
        }

        if ($reassignRemaining) {
            if (in_array($planStatus, array('COMPLETED', 'CANCELLED'), true)) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'Choose Ready or In Progress before reassigning unfinished work.'));
            }
            if (!replen_plan_user_exists($conn1, $assignedTo)) {
                $conn1->rollBack();
                replen_plan_response(400, array('success' => false, 'message' => 'Select a valid HEP assignee for the unfinished work.'));
            }
            $assignmentCount = $conn1->prepare("SELECT COUNT(*) FROM hep.replen_reslot_plan_items
                WHERE plan_id = ? AND item_status IN ('READY', 'IN_PROGRESS', 'BLOCKED')
                    AND UPPER(COALESCE(assigned_to, '')) <> ?");
            $assignmentCount->execute(array($planId, $assignedTo));
            if ((int) $assignmentCount->fetchColumn() > 0) {
                $reassign = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET assigned_to = ?, updated_at = NOW()
                    WHERE plan_id = ? AND item_status IN ('READY', 'IN_PROGRESS', 'BLOCKED')");
                $reassign->execute(array($assignedTo, $planId));
                $changes[] = 'All unfinished actions reassigned to ' . $assignedTo . '.';
            }
        }

        if (count($changes) === 0) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'No plan changes were entered.'));
        }

        $completedAt = $planStatus === 'COMPLETED'
            ? ($plan['completed_at'] ? $plan['completed_at'] : date('Y-m-d H:i:s'))
            : null;
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plans SET
                plan_name = ?, planned_date = ?, plan_status = ?, completed_at = ?, notes = ?,
                updated_at = NOW(), row_version = row_version + 1
            WHERE plan_id = ?");
        $update->execute(array($planName, $plannedDate, $planStatus, $completedAt, $notes, $planId));
        $eventType = $planStatus !== $plan['plan_status'] ? 'PLAN_STATUS_CHANGED' : 'PLAN_UPDATED';
        replen_plan_event($conn1, $planId, null, $eventType, $plan['plan_status'], $planStatus, $user, implode(' ', $changes));
        $conn1->commit();
        replen_plan_response(200, array(
            'success' => true,
            'message' => 'The saved plan was updated.',
            'plan_id' => $planId,
            'row_version' => (int) $plan['row_version'] + 1
        ));
    }

    if ($action === 'cancel_plan') {
        $planId = isset($input['plan_id']) ? (int) $input['plan_id'] : 0;
        $note = isset($input['note']) ? trim($input['note']) : '';
        $statement = $conn1->prepare("SELECT * FROM hep.replen_reslot_plans WHERE plan_id = ? AND warehouse = ? FOR UPDATE");
        $statement->execute(array($planId, $warehouse));
        $plan = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            $conn1->rollBack();
            replen_plan_response(404, array('success' => false, 'message' => 'Plan not found.'));
        }
        if ($plan['plan_status'] === 'COMPLETED' || $plan['plan_status'] === 'CANCELLED') {
            $conn1->rollBack();
            replen_plan_response(409, array('success' => false, 'message' => 'This plan is already terminal.'));
        }
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plans SET plan_status = 'CANCELLED', updated_at = NOW(), row_version = row_version + 1 WHERE plan_id = ?");
        $update->execute(array($planId));
        $delete = $conn1->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_id = ?");
        $delete->execute(array($planId));
        replen_plan_event($conn1, $planId, null, 'PLAN_CANCELLED', $plan['plan_status'], 'CANCELLED', $user, $note !== '' ? $note : 'Cancelled by plan owner.');
        $conn1->commit();
        replen_plan_response(200, array('success' => true, 'message' => 'The plan was cancelled.'));
    }

    $planItemId = isset($input['plan_item_id']) ? (int) $input['plan_item_id'] : 0;
    $item = replen_plan_load_item($conn1, $planItemId);
    if (!$item || strtoupper($item['warehouse']) !== $warehouse) {
        $conn1->rollBack();
        replen_plan_response(404, array('success' => false, 'message' => 'Plan action not found.'));
    }
    if (!replen_plan_can_update($item, $user)) {
        $conn1->rollBack();
        replen_plan_response(403, array('success' => false, 'message' => 'Only the plan owner or assigned user can update this action.'));
    }
    if ($item['plan_status'] === 'CANCELLED' || $item['plan_status'] === 'COMPLETED') {
        $conn1->rollBack();
        replen_plan_response(409, array('success' => false, 'message' => 'This plan is no longer active.'));
    }

    if ($action === 'assign_action') {
        if (strtoupper($item['owner_user']) !== $user) {
            $conn1->rollBack();
            replen_plan_response(403, array('success' => false, 'message' => 'Only the plan owner can reassign actions.'));
        }
        $assignedTo = isset($input['assigned_to']) ? strtoupper(trim($input['assigned_to'])) : '';
        if (!replen_plan_user_exists($conn1, $assignedTo)) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Select a valid HEP assignee.'));
        }
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET assigned_to = ?, updated_at = NOW() WHERE plan_item_id = ?");
        $update->execute(array($assignedTo, $planItemId));
        replen_plan_event($conn1, $item['plan_id'], $planItemId, 'ACTION_ASSIGNED', $item['item_status'], $item['item_status'], $user, 'Assigned to ' . $assignedTo . '.');
        $conn1->commit();
        replen_plan_response(200, array('success' => true, 'message' => 'The action was reassigned.'));
    }

    if ($action === 'start_action') {
        if (!in_array($item['item_status'], array('READY', 'BLOCKED'), true)) {
            $conn1->rollBack();
            replen_plan_response(409, array('success' => false, 'message' => 'Only ready or resolved blocked actions can be started.'));
        }
        if ($item['dependency_item_id']) {
            $dependencyStatement = $conn1->prepare("SELECT item_status FROM hep.replen_reslot_plan_items WHERE plan_item_id = ?");
            $dependencyStatement->execute(array($item['dependency_item_id']));
            if ($dependencyStatement->fetchColumn() !== 'COMPLETED') {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'Complete the enabling move before starting this action.'));
            }
        }
        if ($item['action_type'] !== 'MAX_MIN_ADJUSTMENT') {
            $reservationStatement = $conn1->prepare("SELECT COUNT(*) FROM hep.replen_reslot_location_reservations WHERE warehouse = ? AND target_location = ? AND plan_item_id = ?");
            $reservationStatement->execute(array($warehouse, $item['target_location'], $planItemId));
            if ((int) $reservationStatement->fetchColumn() !== 1) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The target-location reservation is no longer valid.'));
            }
            $sourceStatement = $conn1->prepare("SELECT COUNT(*) FROM hep.item_location WHERE loc_location = ? AND loc_item = ?");
            $sourceStatement->execute(array($item['from_location'], $item['item_number']));
            if ((int) $sourceStatement->fetchColumn() !== 1) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The item is no longer in the planned source location. Refresh and replan.'));
            }
            $targetStatement = $conn1->prepare("SELECT loc_item FROM hep.item_location WHERE loc_location = ? LIMIT 1");
            $targetStatement->execute(array($item['target_location']));
            $targetOccupant = $targetStatement->fetchColumn();
            if ($targetOccupant !== false && (int) $targetOccupant !== (int) $item['item_number']) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The target location is currently occupied.'));
            }
        }
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET item_status = 'IN_PROGRESS', started_at = NOW(), updated_at = NOW() WHERE plan_item_id = ?");
        $update->execute(array($planItemId));
        replen_plan_event($conn1, $item['plan_id'], $planItemId, 'ACTION_STARTED', $item['item_status'], 'IN_PROGRESS', $user, 'Floor action started.');
        replen_plan_refresh_status($conn1, $item['plan_id']);
        $conn1->commit();
        replen_plan_response(200, array('success' => true, 'message' => 'The floor action is now in progress.'));
    }

    if ($action === 'complete_action') {
        if (!in_array($item['item_status'], array('READY', 'IN_PROGRESS', 'BLOCKED'), true)) {
            $conn1->rollBack();
            replen_plan_response(409, array('success' => false, 'message' => 'This action cannot be completed from its current status.'));
        }
        if ($item['dependency_item_id']) {
            $dependencyStatement = $conn1->prepare("SELECT item_status FROM hep.replen_reslot_plan_items WHERE plan_item_id = ?");
            $dependencyStatement->execute(array($item['dependency_item_id']));
            if ($dependencyStatement->fetchColumn() !== 'COMPLETED') {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'Complete the enabling move first.'));
            }
        }
        $actualLocation = isset($input['actual_location']) ? strtoupper(trim($input['actual_location'])) : strtoupper((string) $item['target_location']);
        $actualMax = isset($input['actual_max']) ? (int) $input['actual_max'] : -1;
        $actualMin = isset($input['actual_min']) ? (int) $input['actual_min'] : -1;
        $note = isset($input['note']) ? trim($input['note']) : '';
        if ($actualLocation === '' || $actualMax <= 0 || $actualMin < 0 || $actualMin > $actualMax) {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Enter the actual location and a valid actual max/min.'));
        }
        if ($actualLocation !== strtoupper((string) $item['target_location']) && $note === '') {
            $conn1->rollBack();
            replen_plan_response(400, array('success' => false, 'message' => 'Explain why the actual location differs from the plan.'));
        }
        if ($item['action_type'] !== 'MAX_MIN_ADJUSTMENT') {
            $reservationStatement = $conn1->prepare("SELECT plan_item_id FROM hep.replen_reslot_location_reservations WHERE warehouse = ? AND target_location = ? FOR UPDATE");
            $reservationStatement->execute(array($warehouse, $actualLocation));
            $reservedForItem = $reservationStatement->fetchColumn();
            if ($actualLocation === strtoupper((string) $item['target_location']) && (int) $reservedForItem !== $planItemId) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The planned target-location reservation is no longer valid.'));
            }
            if ($actualLocation !== strtoupper((string) $item['target_location']) && $reservedForItem !== false
                && (int) $reservedForItem !== $planItemId) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The actual destination is reserved by another action.'));
            }
            $slotStatement = $conn1->prepare("SELECT COUNT(*) FROM hep.slotmaster WHERE slotmaster_branch = ? AND slotmaster_loc = ?
                AND COALESCE(slotmaster_block, '') = ''
                AND slotmaster_level = ? AND slotmaster_tier = ? AND slotmaster_dimgroup = ?
                AND CAST(slotmaster_grdeep AS UNSIGNED) = ?");
            $slotStatement->execute(array($warehouse, $actualLocation, $item['level_code'], $item['suggested_tier'], $item['suggested_grid'], $item['suggested_depth']));
            if ((int) $slotStatement->fetchColumn() !== 1) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The actual destination is not an eligible target for this item.'));
            }
            $occupancyStatement = $conn1->prepare("SELECT loc_item FROM hep.item_location WHERE loc_location = ? LIMIT 1");
            $occupancyStatement->execute(array($actualLocation));
            $occupant = $occupancyStatement->fetchColumn();
            if ($occupant !== false && (int) $occupant !== (int) $item['item_number']) {
                $conn1->rollBack();
                replen_plan_response(409, array('success' => false, 'message' => 'The actual destination is occupied by another item.'));
            }
        }
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET
                item_status = 'COMPLETED', completed_at = NOW(), completed_by = ?, actual_location = ?,
                actual_max = ?, actual_min = ?, execution_note = ?, updated_at = NOW()
            WHERE plan_item_id = ?");
        $update->execute(array($user, $actualLocation, $actualMax, $actualMin, $note, $planItemId));
        $delete = $conn1->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_item_id = ?");
        $delete->execute(array($planItemId));
        replen_plan_event($conn1, $item['plan_id'], $planItemId, 'ACTION_COMPLETED', $item['item_status'], 'COMPLETED', $user, $note);
        $planStatus = replen_plan_refresh_status($conn1, $item['plan_id']);
        $conn1->commit();
        replen_plan_response(200, array('success' => true, 'message' => 'The action was completed.', 'plan_status' => $planStatus));
    }

    $note = isset($input['note']) ? trim($input['note']) : '';
    if ($note === '') {
        $conn1->rollBack();
        replen_plan_response(400, array('success' => false, 'message' => 'Enter a reason for this status change.'));
    }

    if ($action === 'block_action') {
        if (!in_array($item['item_status'], array('READY', 'IN_PROGRESS'), true)) {
            $conn1->rollBack();
            replen_plan_response(409, array('success' => false, 'message' => 'Only ready or in-progress actions can be blocked.'));
        }
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET item_status = 'BLOCKED', execution_note = ?, updated_at = NOW() WHERE plan_item_id = ?");
        $update->execute(array($note, $planItemId));
        replen_plan_event($conn1, $item['plan_id'], $planItemId, 'ACTION_BLOCKED', $item['item_status'], 'BLOCKED', $user, $note);
        replen_plan_refresh_status($conn1, $item['plan_id']);
        $conn1->commit();
        replen_plan_response(200, array('success' => true, 'message' => 'The action was blocked for review.'));
    }

    if ($action === 'skip_action') {
        if (!in_array($item['item_status'], array('READY', 'IN_PROGRESS', 'BLOCKED'), true)) {
            $conn1->rollBack();
            replen_plan_response(409, array('success' => false, 'message' => 'This action cannot be skipped from its current status.'));
        }
        $update = $conn1->prepare("UPDATE hep.replen_reslot_plan_items SET item_status = 'SKIPPED', execution_note = ?, updated_at = NOW() WHERE plan_item_id = ?");
        $update->execute(array($note, $planItemId));
        $delete = $conn1->prepare("DELETE FROM hep.replen_reslot_location_reservations WHERE plan_item_id = ?");
        $delete->execute(array($planItemId));
        replen_plan_event($conn1, $item['plan_id'], $planItemId, 'ACTION_SKIPPED', $item['item_status'], 'SKIPPED', $user, $note);
        replen_plan_block_dependents($conn1, $item['plan_id'], $planItemId, $user, 'Enabling action was skipped: ' . $note);
        $planStatus = replen_plan_refresh_status($conn1, $item['plan_id']);
        $conn1->commit();
        replen_plan_response(200, array('success' => true, 'message' => 'The action was skipped and dependent work was blocked.', 'plan_status' => $planStatus));
    }

    $conn1->rollBack();
    replen_plan_response(400, array('success' => false, 'message' => 'No action was performed.'));
} catch (Exception $exception) {
    if (isset($conn1) && $conn1 instanceof PDO && $conn1->inTransaction()) {
        $conn1->rollBack();
    }
    replen_plan_response(500, array('success' => false, 'message' => 'The replenishment plan could not be updated.'));
}
