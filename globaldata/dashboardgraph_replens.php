<?php

include_once '../connection/connection_details.php';
$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


$result1 = $conn1->prepare("SELECT 
                                *
                                
                            FROM
                                hep.slottingscore_hist");
$result1->execute();



$rows = array();
$rows['name'] = 'Date';
$rows1 = array();
$rows1['name'] = 'Loose Replen Reduction';
$rows2 = array();
$rows2['name'] = 'Case Replen Reduction';


foreach ($result1 as $row) {
    $rows['data'][] = $row['slottingscore_hist_DATE'];  //Push fiscal month-year to array
    $rows1['data'][] = $row['slottingscore_hist_LSEMOVES'] * 1;  //Loose Moves
    $rows2['data'][] = $row['slottingscore_hist_CSEMOVES'] * 1; //case Moves
}



$result = array();
array_push($result, $rows);
array_push($result, $rows1);
array_push($result, $rows2);



print json_encode($result);