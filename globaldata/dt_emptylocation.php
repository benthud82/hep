
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

$emptylocsql = $aseriesconn->prepare("SELECT LMLOC#, LMTIER, LMGRD5, LMDEEP FROM HSIPCORDTA.NPFLSM WHERE LMWHSE = $var_whse and LMITEM = ' ' and LMPRIM = 'P' and LMTIER = '$var_tier'");
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
