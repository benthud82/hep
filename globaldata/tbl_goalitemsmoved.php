
<?php

include_once '../sessioninclude.php';
include_once '../connection/connection_details.php';

ini_set('max_execution_time', 99999);
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

include_once '../connection/connection_details.php';

$vectormapdata = $conn1->prepare("SELECT 
                                                                    ' ',
                                                                    goal_item,
                                                                    goal_pkgu,
                                                                    goal_movedtsm,
                                                                    goal_movedate,
                                                                    TRUNCATE(SCORE_TOTALSCORE * 100, 2) AS CURRENT_SCORE,
                                                                    @SCOREATMOVE:=TRUNCATE((SELECT 
                                                                                histitem_totalscore
                                                                            FROM
                                                                                hep.slottingscore_hist_item B
                                                                            WHERE
                                                                                histitem_item = A.goal_item
                                                                                    AND histitem_whse = A.goal_whse
                                                                                    AND histitem_date = A.goal_movedate
                                                                            ORDER BY histitem_date DESC
                                                                            LIMIT 1) * 100,
                                                                        2) AS SCOREATMOVE,
                                                                    TRUNCATE((SCORE_TOTALSCORE * 100) - @SCOREATMOVE,
                                                                        2) AS SCOREINC
                                                                FROM
                                                                    hep.itemsmoved_2018goal A
                                                                        JOIN
                                                                    hep.slottingscore ON goal_whse = SCORE_WHSE
                                                                        AND goal_item = SCORE_ITEM
                                                                        AND goal_pkgu = SCORE_PKGU
                                                                WHERE
                                                                    goal_whse = $var_whse;");
$vectormapdata->execute();
$vectormapdataarray = $vectormapdata->fetchAll(pdo::FETCH_ASSOC);



$output = array(
    "aaData" => array()
);
$row = array();

foreach ($vectormapdataarray as $key => $value) {
    $row[] = array_values($vectormapdataarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
