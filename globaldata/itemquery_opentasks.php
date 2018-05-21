<?php

include_once '../connection/connection_details.php';
$var_userid = strtoupper($_GET['userid']);
$var_itemnum = strtoupper($_GET['itemnum']);

$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


$opentasks = $conn1->prepare("SELECT 
                                                            openactions_id,
                                                            openactions_assignedby,
                                                            openactions_assignedto,
                                                            openactions_assigneddate,
                                                            openactions_comment,
                                                            ' '
                                                        FROM
                                                            hep.slottingdb_itemactions
                                                        WHERE
                                                           openactions_item = $var_itemnum
                                                                    and openactions_status = 'OPEN';");
$opentasks->execute();
$opentasksarray = $opentasks->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($opentasksarray as $key => $value) {
    $row[] = array_values($opentasksarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
