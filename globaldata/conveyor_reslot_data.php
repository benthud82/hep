<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['Login']) || $_SESSION['Login'] !== 'YES') {
    http_response_code(401);
    echo json_encode(array(
        'success' => false,
        'message' => 'Your session has expired. Sign in again to view recommendations.'
    ));
    exit;
}

date_default_timezone_set('America/Chicago');

try {
    include_once '../connection/connection_details.php';

    $view = isset($_GET['view']) ? strtolower(trim($_GET['view'])) : 'rows';
    if (!in_array($view, array('summary', 'rows'), true)) {
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'message' => 'Unsupported report view.'
        ));
        exit;
    }

    $levels = array(
        'A' => array('level' => 'A', 'capacity' => 0, 'recommended' => 0, 'move_in' => 0, 'move_out' => 0, 'planned_open' => 0, 'utilization_pct' => 0),
        'B' => array('level' => 'B', 'capacity' => 0, 'recommended' => 0, 'move_in' => 0, 'move_out' => 0, 'planned_open' => 0, 'utilization_pct' => 0),
        'C' => array('level' => 'C', 'capacity' => 0, 'recommended' => 0, 'move_in' => 0, 'move_out' => 0, 'planned_open' => 0, 'utilization_pct' => 0)
    );

    $capacitySql = "SELECT
                        slotmaster_level AS level,
                        COUNT(*) AS capacity
                    FROM hep.slotmaster
                    WHERE slotmaster_level IN ('A', 'B', 'C')
                        AND slotmaster_tier = 'L02'
                        AND slotmaster_locdesc = 'SD1'
                        AND slotmaster_loc REGEXP '^[ABC][1-6]00[0-9]{3}$'
                        AND slotmaster_pickzone REGEXP '^[ABC][1-6]$'
                    GROUP BY slotmaster_level";
    $capacityResult = $conn1->query($capacitySql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($capacityResult as $row) {
        if (isset($levels[$row['level']])) {
            $levels[$row['level']]['capacity'] = (int) $row['capacity'];
        }
    }

    $recommendedSql = "SELECT
                            CUR_LEVEL AS level,
                            COUNT(*) AS recommended
                        FROM hep.my_npfmvc
                        WHERE CUR_LEVEL IN ('A', 'B', 'C')
                            AND SUGGESTED_TIER = 'L02'
                        GROUP BY CUR_LEVEL";
    $recommendedResult = $conn1->query($recommendedSql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recommendedResult as $row) {
        if (isset($levels[$row['level']])) {
            $levels[$row['level']]['recommended'] = (int) $row['recommended'];
        }
    }

    $movementSql = "SELECT
                        N.CUR_LEVEL AS level,
                        SUM(CASE
                            WHEN CONV.slotmaster_loc IS NULL AND N.SUGGESTED_TIER = 'L02' THEN 1
                            ELSE 0
                        END) AS move_in,
                        SUM(CASE
                            WHEN CONV.slotmaster_loc IS NOT NULL AND N.SUGGESTED_TIER <> 'L02' THEN 1
                            ELSE 0
                        END) AS move_out
                    FROM hep.my_npfmvc N
                    LEFT JOIN hep.slotmaster CONV
                        ON CONV.slotmaster_loc = N.CUR_LOCATION
                        AND CONV.slotmaster_level IN ('A', 'B', 'C')
                        AND CONV.slotmaster_tier = 'L02'
                        AND CONV.slotmaster_locdesc = 'SD1'
                        AND CONV.slotmaster_loc REGEXP '^[ABC][1-6]00[0-9]{3}$'
                        AND CONV.slotmaster_pickzone REGEXP '^[ABC][1-6]$'
                    WHERE N.CUR_LEVEL IN ('A', 'B', 'C')
                    GROUP BY N.CUR_LEVEL";
    $movementResult = $conn1->query($movementSql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($movementResult as $row) {
        if (isset($levels[$row['level']])) {
            $levels[$row['level']]['move_in'] = (int) $row['move_in'];
            $levels[$row['level']]['move_out'] = (int) $row['move_out'];
        }
    }

    $totals = array(
        'level' => 'TOTAL',
        'capacity' => 0,
        'recommended' => 0,
        'move_in' => 0,
        'move_out' => 0,
        'planned_open' => 0,
        'utilization_pct' => 0
    );

    foreach ($levels as $level => $values) {
        $levels[$level]['planned_open'] = max(0, $values['capacity'] - $values['recommended']);
        if ($values['capacity'] > 0) {
            $levels[$level]['utilization_pct'] = round(($values['recommended'] / $values['capacity']) * 100, 1);
        }

        $totals['capacity'] += $levels[$level]['capacity'];
        $totals['recommended'] += $levels[$level]['recommended'];
        $totals['move_in'] += $levels[$level]['move_in'];
        $totals['move_out'] += $levels[$level]['move_out'];
        $totals['planned_open'] += $levels[$level]['planned_open'];
    }

    if ($totals['capacity'] > 0) {
        $totals['utilization_pct'] = round(($totals['recommended'] / $totals['capacity']) * 100, 1);
    }
    $totals['total_actions'] = $totals['move_in'] + $totals['move_out'];

    $impactSql = "SELECT
                      COALESCE(SUM(O.OPT_CURRDAILYFT), 0) AS current_walk_meters_day,
                      COALESCE(SUM(O.OPT_SHLDDAILYFT), 0) AS implied_walk_meters_day,
                      COALESCE(SUM(O.OPT_CURRDAILYFT - O.OPT_SHLDDAILYFT), 0) AS daily_walk_reduction_meters,
                      COALESCE(SUM(N.CURRENT_IMPMOVES), 0) AS current_replen_day,
                      COALESCE(SUM(N.SUGGESTED_IMPMOVES), 0) AS implied_replen_day,
                      COALESCE(SUM(N.CURRENT_IMPMOVES - N.SUGGESTED_IMPMOVES), 0) AS daily_replen_reduction
                  FROM hep.my_npfmvc N
                  JOIN hep.optimalbay O
                      ON O.OPT_WHSE = N.WAREHOUSE
                      AND O.OPT_ITEM = N.ITEM_NUMBER
                      AND O.OPT_PKGU = N.PACKAGE_UNIT
                      AND O.OPT_CSLS = N.PACKAGE_TYPE
                      AND O.OPT_LEVEL = N.CUR_LEVEL
                  LEFT JOIN hep.slotmaster CONV
                      ON CONV.slotmaster_loc = N.CUR_LOCATION
                      AND CONV.slotmaster_level IN ('A', 'B', 'C')
                      AND CONV.slotmaster_tier = 'L02'
                      AND CONV.slotmaster_locdesc = 'SD1'
                      AND CONV.slotmaster_loc REGEXP '^[ABC][1-6]00[0-9]{3}$'
                      AND CONV.slotmaster_pickzone REGEXP '^[ABC][1-6]$'
                  WHERE N.CUR_LEVEL IN ('A', 'B', 'C')
                      AND (
                          (CONV.slotmaster_loc IS NULL AND N.SUGGESTED_TIER = 'L02')
                          OR
                          (CONV.slotmaster_loc IS NOT NULL AND N.SUGGESTED_TIER <> 'L02')
                      )";
    $impactRow = $conn1->query($impactSql)->fetch(PDO::FETCH_ASSOC);
    $dailyWalkReductionMeters = (float) $impactRow['daily_walk_reduction_meters'];
    $dailyReplenReduction = (float) $impactRow['daily_replen_reduction'];
    $impact = array(
        'current_walk_meters_day' => round((float) $impactRow['current_walk_meters_day'], 1),
        'implied_walk_meters_day' => round((float) $impactRow['implied_walk_meters_day'], 1),
        'daily_walk_reduction_meters' => round($dailyWalkReductionMeters, 1),
        'current_replen_day' => round((float) $impactRow['current_replen_day'], 3),
        'implied_replen_day' => round((float) $impactRow['implied_replen_day'], 3),
        'daily_replen_reduction' => round($dailyReplenReduction, 2)
    );
    $impact['annual_walk_reduction_km'] = round(($dailyWalkReductionMeters * 253) / 1000, 1);
    $impact['annual_replen_reduction'] = round($dailyReplenReduction * 253);

    $modelAsOf = null;
    $modelDateResult = $conn1->query("SELECT MAX(slottingscore_hist_DATE) AS model_as_of FROM hep.slottingscore_hist")->fetch(PDO::FETCH_ASSOC);
    if ($modelDateResult && !empty($modelDateResult['model_as_of'])) {
        $modelAsOf = $modelDateResult['model_as_of'];
    }

    $response = array(
        'success' => true,
        'model_as_of' => $modelAsOf,
        'read_at' => date('c'),
        'summary' => array_values($levels),
        'totals' => $totals,
        'impact' => $impact
    );

    if ($view === 'rows') {
        // HEP walking distances are meters. The OPT_*WALKFEET database column names are
        // legacy names; their generating calculation uses the HEP rate of 1.4 meters/second.
        $rowsSql = "SELECT
                        CASE
                            WHEN CONV.slotmaster_loc IS NULL AND N.SUGGESTED_TIER = 'L02' THEN 'MOVE_IN'
                            WHEN CONV.slotmaster_loc IS NOT NULL AND N.SUGGESTED_TIER <> 'L02' THEN 'MOVE_OUT'
                        END AS action,
                        N.CUR_LEVEL AS level,
                        N.ITEM_NUMBER AS item_number,
                        N.PACKAGE_UNIT AS package_unit,
                        N.PACKAGE_TYPE AS package_type,
                        N.CUR_LOCATION AS current_location,
                        LEFT(N.CUR_LOCATION, 4) AS current_band,
                        COALESCE(CURR.slotmaster_tier, N.LMTIER) AS current_tier,
                        COALESCE(CURR.slotmaster_dimgroup, N.LMGRD5) AS current_grid,
                        CAST(COALESCE(CURR.slotmaster_grdeep, N.LMDEEP) AS UNSIGNED) AS current_depth,
                        ROUND(O.OPT_CURRWALKFEET) AS current_walk_distance,
                        N.SUGGESTED_TIER AS suggested_tier,
                        N.SUGGESTED_GRID5 AS suggested_grid,
                        CAST(N.SUGGESTED_DEPTH AS UNSIGNED) AS suggested_depth,
                        ROUND(O.OPT_OPTWALKFEET) AS suggested_walk_distance,
                        COALESCE(TARGET.target_bands, '') AS eligible_bands,
                        ROUND(N.AVG_DAILY_PICK, 2) AS avg_daily_picks,
                        ROUND(O.OPT_PPCCALC, 2) AS target_pick_density,
                        ROUND(
                            CASE
                                WHEN CURR.slotmaster_usecube > 0
                                    THEN (N.AVG_DAILY_PICK / CURR.slotmaster_usecube) * 1000
                                ELSE 0
                            END,
                            2
                        ) AS current_pick_density,
                        ROUND(O.OPT_ADDTLFTPERDAY, 1) AS daily_walk_reduction,
                        ROUND(N.CURRENT_IMPMOVES - N.SUGGESTED_IMPMOVES, 2) AS daily_replen_reduction
                    FROM hep.my_npfmvc N
                    JOIN hep.optimalbay O
                        ON O.OPT_WHSE = N.WAREHOUSE
                        AND O.OPT_ITEM = N.ITEM_NUMBER
                        AND O.OPT_PKGU = N.PACKAGE_UNIT
                        AND O.OPT_CSLS = N.PACKAGE_TYPE
                        AND O.OPT_LEVEL = N.CUR_LEVEL
                    LEFT JOIN hep.slotmaster CURR
                        ON CURR.slotmaster_loc = N.CUR_LOCATION
                    LEFT JOIN hep.slotmaster CONV
                        ON CONV.slotmaster_loc = N.CUR_LOCATION
                        AND CONV.slotmaster_level IN ('A', 'B', 'C')
                        AND CONV.slotmaster_tier = 'L02'
                        AND CONV.slotmaster_locdesc = 'SD1'
                        AND CONV.slotmaster_loc REGEXP '^[ABC][1-6]00[0-9]{3}$'
                        AND CONV.slotmaster_pickzone REGEXP '^[ABC][1-6]$'
                    LEFT JOIN (
                        SELECT
                            slotmaster_level AS level,
                            slotmaster_dimgroup AS grid,
                            CAST(slotmaster_grdeep AS UNSIGNED) AS depth,
                            ROUND(slotmaster_distance) AS walk_distance,
                            GROUP_CONCAT(
                                DISTINCT LEFT(slotmaster_loc, 4)
                                ORDER BY LEFT(slotmaster_loc, 4)
                                SEPARATOR ', '
                            ) AS target_bands
                        FROM hep.slotmaster
                        WHERE slotmaster_level IN ('A', 'B', 'C')
                            AND slotmaster_tier = 'L02'
                            AND slotmaster_locdesc = 'SD1'
                            AND slotmaster_loc REGEXP '^[ABC][1-6]00[0-9]{3}$'
                            AND slotmaster_pickzone REGEXP '^[ABC][1-6]$'
                        GROUP BY
                            slotmaster_level,
                            slotmaster_dimgroup,
                            CAST(slotmaster_grdeep AS UNSIGNED),
                            ROUND(slotmaster_distance)
                    ) TARGET
                        ON TARGET.level = N.CUR_LEVEL
                        AND TARGET.grid = N.SUGGESTED_GRID5
                        AND TARGET.depth = CAST(N.SUGGESTED_DEPTH AS UNSIGNED)
                        AND TARGET.walk_distance = ROUND(O.OPT_OPTWALKFEET)
                    WHERE N.CUR_LEVEL IN ('A', 'B', 'C')
                        AND (
                            (CONV.slotmaster_loc IS NULL AND N.SUGGESTED_TIER = 'L02')
                            OR
                            (CONV.slotmaster_loc IS NOT NULL AND N.SUGGESTED_TIER <> 'L02')
                        )";

        $rows = $conn1->query($rowsSql)->fetchAll(PDO::FETCH_ASSOC);
        $groupedRows = array();

        foreach ($rows as $row) {
            $row['package_unit'] = (int) $row['package_unit'];
            $row['current_depth'] = (int) $row['current_depth'];
            $row['current_walk_distance'] = (int) $row['current_walk_distance'];
            $row['suggested_depth'] = (int) $row['suggested_depth'];
            $row['suggested_walk_distance'] = (int) $row['suggested_walk_distance'];
            $row['avg_daily_picks'] = (float) $row['avg_daily_picks'];
            $row['target_pick_density'] = (float) $row['target_pick_density'];
            $row['current_pick_density'] = (float) $row['current_pick_density'];
            $row['daily_walk_reduction'] = (float) $row['daily_walk_reduction'];
            $row['daily_replen_reduction'] = (float) $row['daily_replen_reduction'];
            $row['reason'] = $row['action'] === 'MOVE_IN'
                ? 'Model prioritizes this item for conveyor pick density.'
                : 'Model recommends this item move to ' . $row['suggested_tier'] . ' outside the conveyor tier.';

            $groupKey = $row['action'] . '|' . $row['level'];
            if (!isset($groupedRows[$groupKey])) {
                $groupedRows[$groupKey] = array();
            }
            $groupedRows[$groupKey][] = $row;
        }

        $rankedRows = array();
        foreach ($groupedRows as $groupKey => $groupRows) {
            $isMoveIn = strpos($groupKey, 'MOVE_IN|') === 0;

            usort($groupRows, function ($left, $right) use ($isMoveIn) {
                if ($isMoveIn) {
                    $sortFields = array('target_pick_density', 'avg_daily_picks', 'daily_walk_reduction');
                    foreach ($sortFields as $field) {
                        if ($left[$field] != $right[$field]) {
                            return ($left[$field] > $right[$field]) ? -1 : 1;
                        }
                    }
                } else {
                    if ($left['current_pick_density'] != $right['current_pick_density']) {
                        return ($left['current_pick_density'] < $right['current_pick_density']) ? -1 : 1;
                    }
                    if ($left['daily_replen_reduction'] != $right['daily_replen_reduction']) {
                        return ($left['daily_replen_reduction'] > $right['daily_replen_reduction']) ? -1 : 1;
                    }
                    if ($left['avg_daily_picks'] != $right['avg_daily_picks']) {
                        return ($left['avg_daily_picks'] < $right['avg_daily_picks']) ? -1 : 1;
                    }
                }

                return strcmp((string) $left['item_number'], (string) $right['item_number']);
            });

            foreach ($groupRows as $index => $row) {
                $row['priority'] = $index + 1;
                $rankedRows[] = $row;
            }
        }

        usort($rankedRows, function ($left, $right) {
            $leftAction = $left['action'] === 'MOVE_OUT' ? 1 : 2;
            $rightAction = $right['action'] === 'MOVE_OUT' ? 1 : 2;

            if ($leftAction !== $rightAction) {
                return $leftAction - $rightAction;
            }
            if ($left['level'] !== $right['level']) {
                return strcmp($left['level'], $right['level']);
            }
            return $left['priority'] - $right['priority'];
        });

        $response['rows'] = $rankedRows;
    }

    echo json_encode($response);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Conveyor recommendations are temporarily unavailable.'
    ));
}
