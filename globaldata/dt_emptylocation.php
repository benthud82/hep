
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';
include_once '../../globalincludes/usa_asys.php';

$var_userid = $_GET['userid'];
$var_tier = $_GET['tier'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['slottingDB_users_PRIMDC']);

$emptylocsql = $conn1->prepare("SELECT slotmaster_loc, slotmaster_tier, slotmaster_dimgroup, slotmaster_usedeep FROM hep.slotmaster LEFT JOIN hep.item_location  ON loc_location = slotmaster_loc WHERE loc_location IS NULL  and (slotmaster_locdesc NOT LIKE ('GS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('WK%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('VS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('KH%'))and slotmaster_tier = '$var_tier'");
$emptylocsql->execute();
$emptylocarray = $emptylocsql->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($emptylocarray as $key => $value) {
    $row[] = array_values($emptylocarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
