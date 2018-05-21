
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
                                                                            ' ', maperror_bay, maperror_tier
                                                                        FROM
                                                                            hep.vectormaperrors
                                                                                LEFT JOIN
                                                                            hep.vectormap ON BAY = maperror_bay
                                                                        WHERE
                                                                            BAY IS NULL and maperror_whse = $var_whse
                                                                        ORDER BY maperror_bay ASC");
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
