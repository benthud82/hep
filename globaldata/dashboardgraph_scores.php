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
$rows1['name'] = 'Loose Bottom 100';
$rows2 = array();
$rows2['name'] = 'Loose Bottom 1000';
$rows3 = array();
$rows3['name'] = 'Case Bottom 100';
$rows4 = array();
$rows4['name'] = 'Case Bottom 1000';

foreach ($result1 as $row) {
    $rows['data'][] = $row['slottingscore_hist_DATE'];  //Push fiscal month-year to array
    $rows1['data'][] = $row['slottingscore_hist_LSE100'] * 1;  //Loose Score Bottom 100
    $rows2['data'][] = ($row['slottingscore_hist_LSE1000']) * 1; //Loose Score Bottom 1000
    $rows3['data'][] = $row['slottingscore_hist_CSE100'] * 1;  //Loose Score Bottom 100
    $rows4['data'][] = ($row['slottingscore_hist_CSE1000']) * 1; //Loose Score Bottom 1000

}



$result = array();
array_push($result, $rows);
array_push($result, $rows1);
array_push($result, $rows2);
array_push($result, $rows3);
array_push($result, $rows4);


print json_encode($result);