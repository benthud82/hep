
<?php

if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            } else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

}
ini_set('max_execution_time', 99999);

include_once '../connection/connection_details.php';


$baynum = intval($_GET['baynum']);
$tiersel = ($_GET['tiersel']);
$levelsel= ($_GET['levelsel']);




//pull in distict grids either used or suggested
$distinctgridsql = $conn1->prepare("SELECT DISTINCT
                                                                DIMGROUP AS GRID5_DEP
                                                            FROM
                                                                hep.bay_location
                                                            WHERE
                                                                WALKFEET = $baynum AND TIER = '$tiersel' and LEVEL = '$levelsel'
                                                            ORDER BY VOLUME");
$distinctgridsql->execute();
$distinctgridarray = $distinctgridsql->fetchAll(pdo::FETCH_ASSOC);

//pull in suggested count by grid5_dep
$suggestedgridsql = $conn1->prepare("SELECT 
                                                                SUGGESTED_GRID5 AS SUG_GRID5_DEP,
                                                                COUNT(*) AS SUG_COUNT,
                                                                SUM(SUGGESTED_NEWLOCVOL) as SUGG_VOL
                                                            FROM
                                                                hep.my_npfmvc
                                                                    JOIN
                                                                hep.optimalbay ON OPT_ITEM = ITEM_NUMBER

                                                            WHERE
                                                                SUGGESTED_TIER = '$tiersel'
                                                                    AND OPT_OPTWALKFEET = $baynum and CUR_LEVEL = '$levelsel'
                                                            GROUP BY SUGGESTED_GRID5
                                                            ORDER BY SUGGESTED_NEWLOCVOL ASC");
$suggestedgridsql->execute();
$suggestedgridarray = $suggestedgridsql->fetchAll(pdo::FETCH_ASSOC);

//pull in current count by grid5_dep
$currentgridsql = $conn1->prepare("SELECT 
                                                                DIMGROUP AS CUR_GRID5_DEP, sum(VOLUME) as CUR_VOL, COUNT(*) AS CUR_COUNT
                                                            FROM
                                                                hep.bay_location
                                                            WHERE
                                                                WALKFEET = $baynum
                                                                    AND TIER = '$tiersel'  and LEVEL = '$levelsel'
                                                            GROUP BY DIMGROUP
                                                            ORDER BY VOLUME;");
$currentgridsql->execute();
$currentgridarray = $currentgridsql->fetchAll(pdo::FETCH_ASSOC);

$output = array(
    "aaData" => array()
);
$row = array();


//join all three arrays for complete needs wants table
foreach ($distinctgridarray as $key => $value) {
    $grid5_dep = $distinctgridarray[$key]['GRID5_DEP'];
    //find if grid5 is in suggtested array
    $suggestedkey = array_search($grid5_dep, array_column($suggestedgridarray, 'SUG_GRID5_DEP'));
    if ($suggestedkey !== FALSE) {
        $distinctgridarray[$key]['SUG_COUNT'] = intval($suggestedgridarray[$suggestedkey]['SUG_COUNT']);
    } else {
        $distinctgridarray[$key]['SUG_COUNT'] = 0;
    }


    //find if grid5 is in current array
    $currentkey = array_search($grid5_dep, array_column($currentgridarray, 'CUR_GRID5_DEP'));
    if ($currentkey !== FALSE) {
        $distinctgridarray[$key]['CUR_COUNT'] = intval($currentgridarray[$currentkey]['CUR_COUNT']);
    } else {
        $distinctgridarray[$key]['CUR_COUNT'] = 0;
    }

    //push count +/-
    $distinctgridarray[$key]['PLUS_MINUS_COUNT'] = intval($distinctgridarray[$key]['CUR_COUNT']) - intval($distinctgridarray[$key]['SUG_COUNT']);

    //push volume +/-
    $distinctgridarray[$key]['PLUS_MINUS_VOL'] = intval($currentgridarray[$currentkey]['CUR_VOL'] - $suggestedgridarray[$suggestedkey]['SUGG_VOL']);

    $row[] = array_values($distinctgridarray[$key]);
}

$output['aaData'] = $row;
echo json_encode($output);
