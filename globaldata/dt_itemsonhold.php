
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['slottingDB_users_PRIMDC']);

$onholdsql = $conn1->prepare("SELECT 
                                                            ITEM,
                                                            PKGU,
                                                            HOLDLOCATION,
                                                            HOLDGRID,
                                                            HOLDTIER,
                                                            CASE
                                                                WHEN itemcomments_id > 0 THEN 'SHOW COMMENTS'
                                                            END AS COMMENTS
                                                        FROM
                                                            hep.item_settings
                                                                LEFT JOIN
                                                            hep.slotting_itemcomments ON itemcomments_whse = WHSE
                                                                AND itemcomments_item = ITEM
                                                        WHERE
                                                            WHSE = $var_whse
                                                                AND (HOLDTIER <> ' ' OR HOLDGRID <> ' '
                                                                OR HOLDLOCATION <> ' ');");
$onholdsql->execute();
$onholdarray = $onholdsql->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($onholdarray as $key => $value) {
    $ITEM = $onholdarray[$key]['ITEM'];
    $PKGU = $onholdarray[$key]['PKGU'];
    $HOLDLOCATION = $onholdarray[$key]['HOLDLOCATION'];
    $HOLDGRID = $onholdarray[$key]['HOLDGRID'];
    $HOLDTIER = $onholdarray[$key]['HOLDTIER'];
    $COMMENTS = $onholdarray[$key]['COMMENTS'];




    $rowpush = array($ITEM, $PKGU, $HOLDLOCATION, $HOLDGRID, $HOLDTIER,$COMMENTS);
    $row[] = array_values($rowpush);
}


$output['aaData'] = $row;
echo json_encode($output);
