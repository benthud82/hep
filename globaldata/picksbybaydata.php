
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];
$var_date = date('Y-m-d',  strtotime($_GET['datesel']));


$bayreport = $conn1->prepare("SELECT 
                                    picksbybay_BAY,
                                    picksbybay_PICKS,
                                    WALKFEET,
                                    picksbybay_PICKS * WALKFEET as TOTFEET
                                FROM
                                    hep.picksbybay
                                        join
                                    hep.vectormap ON VECTWHSE = picksbybay_WHSE
                                        and BAY = picksbybay_BAY
                                WHERE
                                    picksbybay_WHSE = $var_whse
                                        and picksbybay_DATE = '$var_date'
                                ORDER BY picksbybay_PICKS * WALKFEET desc ;");
$bayreport->execute();
$bayreportarray = $bayreport->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($bayreportarray as $key => $value) {
    $row[] = array_values($bayreportarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
