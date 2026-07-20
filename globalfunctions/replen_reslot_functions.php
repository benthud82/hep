<?php

function replen_table_exists(PDO $conn, $tableName) {
    $statement = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'hep' AND table_name = ?");
    $statement->execute(array($tableName));
    return (int) $statement->fetchColumn() > 0;
}

function replen_config(PDO $conn) {
    $defaults = array(
        'replens_per_hour' => 15.0,
        'walk_meters_per_second' => 1.4,
        'operating_days' => 253,
        'minimum_ship_occurrences' => 15,
        'maximum_days_since_sale' => 15,
        'stale_after_hours' => 48
    );

    if (!replen_table_exists($conn, 'replen_reslot_config')) {
        return $defaults;
    }

    $row = $conn->query("SELECT * FROM hep.replen_reslot_config WHERE config_id = 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return $defaults;
    }

    foreach ($defaults as $key => $value) {
        if (isset($row[$key])) {
            $defaults[$key] = is_int($value) ? (int) $row[$key] : (float) $row[$key];
        }
    }
    return $defaults;
}

function replen_model_health(PDO $conn, array $config) {
    $health = array(
        'status' => 'UNKNOWN',
        'model_run_id' => null,
        'model_as_of' => null,
        'age_hours' => null,
        'is_stale' => true,
        'occupancy_available' => replen_table_exists($conn, 'item_location'),
        'planning_available' => false,
        'message' => ''
    );

    if (replen_table_exists($conn, 'replen_reslot_model_runs')) {
        $run = $conn->query("SELECT * FROM hep.replen_reslot_model_runs ORDER BY model_run_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($run) {
            $health['status'] = $run['status'];
            $health['model_run_id'] = (int) $run['model_run_id'];
            $health['model_as_of'] = $run['finished_at'];
            $health['message'] = (string) $run['message'];
        }
    }

    if (!$health['model_as_of']) {
        $fallback = $conn->query("SELECT MAX(slottingscore_hist_DATE) FROM hep.slottingscore_hist")->fetchColumn();
        if ($fallback) {
            $health['status'] = 'UNRECORDED';
            $health['model_as_of'] = $fallback . ' 23:59:59';
            $health['message'] = 'The model is readable, but no successful replenishment planner refresh has been recorded.';
        }
    }

    if ($health['model_as_of']) {
        $modelTime = strtotime($health['model_as_of']);
        $health['age_hours'] = round(max(0, time() - $modelTime) / 3600, 1);
        $health['is_stale'] = $health['age_hours'] > (int) $config['stale_after_hours'];
    }

    if (!$health['occupancy_available']) {
        $health['message'] = 'Current item-location occupancy is unavailable. Opportunities can be reviewed, but exact floor plans cannot be created.';
    } elseif ($health['status'] !== 'SUCCESS') {
        $health['message'] = 'Run the HEP refresh successfully before creating exact-location plans.';
    } elseif ($health['is_stale']) {
        $health['message'] = 'The latest successful HEP model refresh is stale. Refresh the model before creating a plan.';
    }

    $health['planning_available'] = $health['occupancy_available'] && $health['status'] === 'SUCCESS' && !$health['is_stale'];
    return $health;
}

function replen_profile_key($level, $tier, $grid, $depth) {
    return strtoupper(trim((string) $level)) . '|' . strtoupper(trim((string) $tier)) . '|' . strtoupper(trim((string) $grid)) . '|' . (int) $depth;
}

function replen_opportunity_key(array $row) {
    return strtoupper(trim((string) $row['warehouse'])) . '|' . (int) $row['item_number'] . '|' . (int) $row['package_unit'] . '|' . strtoupper(trim((string) $row['package_type'])) . '|' . strtoupper(trim((string) $row['level']));
}

function replen_target_profile_summary(PDO $conn, $occupancyAvailable, $reservationsAvailable) {
    $joinOccupancy = $occupancyAvailable
        ? "LEFT JOIN hep.item_location I ON I.loc_location = S.slotmaster_loc"
        : "";
    $joinReservations = $reservationsAvailable
        ? "LEFT JOIN hep.replen_reslot_location_reservations R ON R.warehouse = S.slotmaster_branch AND R.target_location = S.slotmaster_loc"
        : "";
    $vacantExpression = $occupancyAvailable ? "I.loc_location IS NULL" : "0";
    $reservedExpression = $reservationsAvailable ? "R.target_location IS NULL" : "1";

    $sql = "SELECT
                S.slotmaster_level AS level_code,
                S.slotmaster_tier AS tier_code,
                S.slotmaster_dimgroup AS grid_code,
                CAST(S.slotmaster_grdeep AS UNSIGNED) AS depth_value,
                COUNT(*) AS compatible_slots,
                SUM(CASE WHEN {$vacantExpression} AND {$reservedExpression} THEN 1 ELSE 0 END) AS vacant_slots
            FROM hep.slotmaster S
            {$joinOccupancy}
            {$joinReservations}
            WHERE COALESCE(S.slotmaster_block, '') = ''
                AND S.slotmaster_locdesc NOT LIKE 'GS%'
                AND S.slotmaster_locdesc NOT LIKE 'WK%'
                AND S.slotmaster_locdesc NOT LIKE 'VS%'
                AND S.slotmaster_locdesc NOT LIKE 'KH%'
            GROUP BY S.slotmaster_level, S.slotmaster_tier, S.slotmaster_dimgroup, CAST(S.slotmaster_grdeep AS UNSIGNED)";

    $summary = array();
    foreach ($conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $summary[replen_profile_key($row['level_code'], $row['tier_code'], $row['grid_code'], $row['depth_value'])] = array(
            'compatible_slots' => (int) $row['compatible_slots'],
            'vacant_slots' => (int) $row['vacant_slots']
        );
    }
    return $summary;
}

function replen_build_opportunities(PDO $conn, array $config, array $health) {
    $occupancyAvailable = $health['occupancy_available'];
    $settingsAvailable = replen_table_exists($conn, 'item_settings');
    $reservationsAvailable = replen_table_exists($conn, 'replen_reslot_location_reservations');

    $locationSelect = $occupancyAvailable
        ? "I.loc_truefit AS current_max, I.loc_minqty AS current_min,"
        : "NULL AS current_max, NULL AS current_min,";
    $locationJoin = $occupancyAvailable
        ? "LEFT JOIN hep.item_location I ON I.loc_item = N.ITEM_NUMBER AND I.loc_location = N.CUR_LOCATION"
        : "";
    $settingsSelect = $settingsAvailable
        ? "COALESCE(H.hold_tier, '') AS hold_tier, COALESCE(H.hold_grid, '') AS hold_grid, COALESCE(H.hold_location, '') AS hold_location,"
        : "'' AS hold_tier, '' AS hold_grid, '' AS hold_location,";
    $settingsJoin = $settingsAvailable
        ? "LEFT JOIN (
                SELECT WHSE, ITEM, PKGU,
                    MAX(COALESCE(HOLDTIER, '')) AS hold_tier,
                    MAX(COALESCE(HOLDGRID, '')) AS hold_grid,
                    MAX(COALESCE(HOLDLOCATION, '')) AS hold_location
                FROM hep.item_settings
                GROUP BY WHSE, ITEM, PKGU
            ) H ON H.WHSE = N.WAREHOUSE AND H.ITEM = N.ITEM_NUMBER AND H.PKGU = N.PACKAGE_UNIT"
        : "";

    $sql = "SELECT
                N.WAREHOUSE AS warehouse,
                N.ITEM_NUMBER AS item_number,
                N.PACKAGE_UNIT AS package_unit,
                N.PACKAGE_TYPE AS package_type,
                N.CUR_LEVEL AS level_code,
                N.CUR_LOCATION AS current_location,
                N.LMTIER AS current_tier,
                N.LMGRD5 AS current_grid,
                CAST(N.LMDEEP AS UNSIGNED) AS current_depth,
                N.SUGGESTED_TIER AS suggested_tier,
                N.SUGGESTED_GRID5 AS suggested_grid,
                CAST(N.SUGGESTED_DEPTH AS UNSIGNED) AS suggested_depth,
                N.SUGGESTED_MAX AS suggested_max,
                N.SUGGESTED_MIN AS suggested_min,
                N.CURRENT_IMPMOVES AS current_replens_day,
                N.SUGGESTED_IMPMOVES AS suggested_replens_day,
                N.NBR_SHIP_OCC AS shipment_occurrences,
                N.DAYS_FRM_SLE AS days_since_sale,
                N.AVG_DAILY_UNIT AS avg_daily_units,
                N.AVG_DAILY_PICK AS avg_daily_picks,
                N.AVG_INV_OH AS avg_inventory,
                -- OPT_ADDTLFTPERDAY is a legacy-named millimeter value in HEP.
                COALESCE(O.OPT_ADDTLFTPERDAY, 0) / 1000 AS walk_reduction_meters_day,
                COALESCE(O.OPT_OPTWALKFEET, 0) AS optimal_walk_distance,
                COALESCE(D.model_location_rows, 1) AS model_location_rows,
                {$locationSelect}
                {$settingsSelect}
                1 AS row_marker
            FROM hep.my_npfmvc N
            LEFT JOIN hep.optimalbay O
                ON O.OPT_WHSE = N.WAREHOUSE
                AND O.OPT_ITEM = N.ITEM_NUMBER
                AND O.OPT_PKGU = N.PACKAGE_UNIT
                AND O.OPT_CSLS = N.PACKAGE_TYPE
                AND O.OPT_LEVEL = N.CUR_LEVEL
            LEFT JOIN (
                SELECT CUR_LOCATION, COUNT(*) AS model_location_rows
                FROM hep.my_npfmvc
                WHERE PACKAGE_TYPE = '101'
                GROUP BY CUR_LOCATION
            ) D ON D.CUR_LOCATION = N.CUR_LOCATION
            {$locationJoin}
            {$settingsJoin}
            WHERE N.PACKAGE_TYPE = '101'
                AND N.CURRENT_IMPMOVES > N.SUGGESTED_IMPMOVES";

    $profileSummary = replen_target_profile_summary($conn, $occupancyAvailable, $reservationsAvailable);
    $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $opportunities = array();

    foreach ($rows as $row) {
        $replenReduction = (float) $row['current_replens_day'] - (float) $row['suggested_replens_day'];
        $walkReduction = (float) $row['walk_reduction_meters_day'];
        $replenMinutes = $replenReduction * (60 / max(0.01, (float) $config['replens_per_hour']));
        $walkMinutes = $walkReduction / max(0.01, (float) $config['walk_meters_per_second']) / 60;
        $netMinutes = $replenMinutes + $walkMinutes;
        $annualLaborHours = $netMinutes * (int) $config['operating_days'] / 60;
        $profileChanged = strtoupper((string) $row['current_tier']) !== strtoupper((string) $row['suggested_tier'])
            || strtoupper((string) $row['current_grid']) !== strtoupper((string) $row['suggested_grid'])
            || (int) $row['current_depth'] !== (int) $row['suggested_depth'];
        $held = trim((string) $row['hold_tier']) !== '' || trim((string) $row['hold_grid']) !== '' || trim((string) $row['hold_location']) !== '';
        $lowHistory = (int) $row['shipment_occurrences'] < (int) $config['minimum_ship_occurrences']
            || (int) $row['days_since_sale'] > (int) $config['maximum_days_since_sale'];
        $settingsAlreadyMatch = !$profileChanged && $occupancyAvailable && $row['current_max'] !== null
            && (int) $row['current_max'] === (int) $row['suggested_max']
            && (int) $row['current_min'] === (int) $row['suggested_min'];

        $target = array('compatible_slots' => 0, 'vacant_slots' => 0);
        if ($profileChanged) {
            $profileKey = replen_profile_key($row['level_code'], $row['suggested_tier'], $row['suggested_grid'], $row['suggested_depth']);
            if (isset($profileSummary[$profileKey])) {
                $target = $profileSummary[$profileKey];
            }
        }

        if (!$profileChanged) {
            $actionType = 'MAX_MIN_ADJUSTMENT';
        } elseif ($target['vacant_slots'] > 0) {
            $actionType = 'DIRECT_RESLOT';
        } elseif ($target['compatible_slots'] > 0) {
            $actionType = 'DEPENDENCY_RESLOT';
        } else {
            $actionType = 'REVIEW_REQUIRED';
        }

        $reviewReasons = array();
        if (!$health['planning_available']) {
            $reviewReasons[] = 'Planning data is unavailable or stale';
        }
        if ($lowHistory) {
            $reviewReasons[] = 'Low shipment history';
        }
        if ($held) {
            $reviewReasons[] = 'Item has a slotting hold';
        }
        if ((int) $row['model_location_rows'] > 1) {
            $reviewReasons[] = 'Current location has multiple model records';
        }
        if ($netMinutes <= 0) {
            $reviewReasons[] = 'Walking tradeoff makes net labor non-positive';
        }
        if ($profileChanged && $target['compatible_slots'] <= 0) {
            $reviewReasons[] = 'No compatible unblocked target profile';
        }
        if ($settingsAlreadyMatch) {
            $reviewReasons[] = 'Settings already match the model';
        }

        $planningEligible = $health['planning_available'] && !$lowHistory && !$held
            && !$settingsAlreadyMatch && (int) $row['model_location_rows'] === 1 && $netMinutes > 0
            && (($actionType === 'MAX_MIN_ADJUSTMENT') || $target['compatible_slots'] > 0);
        $overrideAllowed = $health['planning_available'] && $lowHistory && !$held
            && !$settingsAlreadyMatch && (int) $row['model_location_rows'] === 1 && $netMinutes > 0
            && (($actionType === 'MAX_MIN_ADJUSTMENT') || $target['compatible_slots'] > 0)
            && $actionType !== 'REVIEW_REQUIRED';

        $opportunity = array(
            'key' => strtoupper((string) $row['warehouse']) . '|' . (int) $row['item_number'] . '|' . (int) $row['package_unit'] . '|' . strtoupper((string) $row['package_type']) . '|' . strtoupper((string) $row['level_code']),
            'warehouse' => strtoupper((string) $row['warehouse']),
            'item_number' => (int) $row['item_number'],
            'package_unit' => (int) $row['package_unit'],
            'package_type' => (string) $row['package_type'],
            'level' => strtoupper((string) $row['level_code']),
            'current_location' => (string) $row['current_location'],
            'current_tier' => (string) $row['current_tier'],
            'current_grid' => (string) $row['current_grid'],
            'current_depth' => (int) $row['current_depth'],
            'suggested_tier' => (string) $row['suggested_tier'],
            'suggested_grid' => (string) $row['suggested_grid'],
            'suggested_depth' => (int) $row['suggested_depth'],
            'current_max' => $row['current_max'] === null ? null : (int) $row['current_max'],
            'current_min' => $row['current_min'] === null ? null : (int) $row['current_min'],
            'suggested_max' => (int) $row['suggested_max'],
            'suggested_min' => (int) $row['suggested_min'],
            'current_replens_day' => round((float) $row['current_replens_day'], 4),
            'suggested_replens_day' => round((float) $row['suggested_replens_day'], 4),
            'replen_reduction_day' => round($replenReduction, 4),
            'annual_replens_avoided' => round($replenReduction * (int) $config['operating_days']),
            'walk_reduction_meters_day' => round($walkReduction, 2),
            'net_minutes_day' => round($netMinutes, 3),
            'annual_labor_hours' => round($annualLaborHours, 2),
            'avg_daily_units' => round((float) $row['avg_daily_units'], 2),
            'avg_daily_picks' => round((float) $row['avg_daily_picks'], 2),
            'avg_inventory' => (int) $row['avg_inventory'],
            'shipment_occurrences' => (int) $row['shipment_occurrences'],
            'days_since_sale' => (int) $row['days_since_sale'],
            'model_location_rows' => (int) $row['model_location_rows'],
            'optimal_walk_distance' => (int) $row['optimal_walk_distance'],
            'action_type' => $actionType,
            'confidence' => $lowHistory ? 'REVIEW' : 'STANDARD',
            'held' => $held,
            'compatible_slots' => (int) $target['compatible_slots'],
            'vacant_slots' => $occupancyAvailable ? (int) $target['vacant_slots'] : null,
            'planning_eligible' => $planningEligible,
            'override_allowed' => $overrideAllowed,
            'review_reasons' => $reviewReasons,
            'reason' => count($reviewReasons) > 0
                ? implode('; ', $reviewReasons)
                : ($actionType === 'MAX_MIN_ADJUSTMENT' ? 'Increase usable pick-face capacity without a physical move.' : 'Move to a larger compatible pick face to reduce replenishment frequency.')
        );
        $opportunities[] = $opportunity;
    }

    usort($opportunities, function ($left, $right) {
        if ($left['planning_eligible'] !== $right['planning_eligible']) {
            return $left['planning_eligible'] ? -1 : 1;
        }
        if ($left['annual_labor_hours'] != $right['annual_labor_hours']) {
            return $left['annual_labor_hours'] > $right['annual_labor_hours'] ? -1 : 1;
        }
        if ($left['annual_replens_avoided'] != $right['annual_replens_avoided']) {
            return $left['annual_replens_avoided'] > $right['annual_replens_avoided'] ? -1 : 1;
        }
        return strcmp($left['key'], $right['key']);
    });

    foreach ($opportunities as $index => $opportunity) {
        $opportunities[$index]['priority'] = $index + 1;
    }
    return $opportunities;
}

function replen_opportunity_summary(array $opportunities, array $config, array $health) {
    $summary = array(
        'opportunity_count' => count($opportunities),
        'physical_reslots' => 0,
        'adjustments' => 0,
        'ready_now' => 0,
        'dependency_reslots' => 0,
        'review_required' => 0,
        'annual_replens_avoided' => 0,
        'annual_labor_hours' => 0,
        'daily_net_minutes' => 0
    );

    foreach ($opportunities as $row) {
        if ($row['action_type'] === 'MAX_MIN_ADJUSTMENT') {
            $summary['adjustments']++;
        } else {
            $summary['physical_reslots']++;
        }
        if ($row['action_type'] === 'DIRECT_RESLOT' && $row['planning_eligible']) {
            $summary['ready_now']++;
        }
        if ($row['action_type'] === 'DEPENDENCY_RESLOT') {
            $summary['dependency_reslots']++;
        }
        if (!$row['planning_eligible'] || $row['action_type'] === 'REVIEW_REQUIRED') {
            $summary['review_required']++;
        }
        if ($row['net_minutes_day'] > 0) {
            $summary['annual_replens_avoided'] += $row['annual_replens_avoided'];
            $summary['annual_labor_hours'] += $row['annual_labor_hours'];
            $summary['daily_net_minutes'] += $row['net_minutes_day'];
        }
    }

    $summary['annual_replens_avoided'] = round($summary['annual_replens_avoided']);
    $summary['annual_labor_hours'] = round($summary['annual_labor_hours'], 1);
    $summary['daily_net_minutes'] = round($summary['daily_net_minutes'], 1);
    $summary['planning_available'] = $health['planning_available'];
    $summary['model_as_of'] = $health['model_as_of'];
    $summary['assumptions'] = $config;
    return $summary;
}

function replen_csrf_token() {
    if (empty($_SESSION['replen_reslot_csrf'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['replen_reslot_csrf'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['replen_reslot_csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['replen_reslot_csrf'];
}

function replen_self_test() {
    $failures = array();
    $config = array('replens_per_hour' => 15.0, 'walk_meters_per_second' => 1.4, 'operating_days' => 253);
    $replenMinutes = 0.5 * (60 / $config['replens_per_hour']);
    $walkMinutes = -84 / $config['walk_meters_per_second'] / 60;
    $net = $replenMinutes + $walkMinutes;
    if (abs($replenMinutes - 2.0) > 0.0001) {
        $failures[] = 'Replenishment time conversion failed.';
    }
    if (abs($walkMinutes + 1.0) > 0.0001) {
        $failures[] = 'Walk time conversion failed.';
    }
    if (abs($net - 1.0) > 0.0001) {
        $failures[] = 'Net labor calculation failed.';
    }
    if (replen_profile_key('a', 'l04', 'f1', '400.00') !== 'A|L04|F1|400') {
        $failures[] = 'Profile key normalization failed.';
    }
    return $failures;
}
