<?php
ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';
$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


$time = strtotime("-1 year", time());
$date = date("Y-m-d", $time);

$result1 = $conn1->prepare("SELECT * FROM hep.feetperpick_summary  WHERE
                                fpp_date >= '$date'");
$result1->execute();



$rows = array();
$rows['name'] = 'Date';
$rows1 = array();
$rows1['name'] = 'FPP';


foreach ($result1 as $row) {
    $rows['data'][] = $row['fpp_date'];
    $rows1['data'][] = $row['fpp_fpp'] * 1;
}


$result = array();
array_push($result, $rows);
array_push($result, $rows1);



print json_encode($result);

