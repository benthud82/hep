<?php

include_once '../connection/connection_details.php';
$var_userid = strtoupper($_GET['userid']);
$var_itemnum = strtoupper($_GET['itemnum']);

$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


$closedtasks = $conn1->prepare("SELECT 
                                                            openactions_assignedby,
                                                            openactions_completeduser,
                                                            openactions_assigneddate,
                                                            openactions_completeddate,
                                                            openactions_comment,
                                                            openactions_completedcomment
                                                        FROM
                                                            hep.slottingdb_itemactions
                                                        WHERE
                                                            openactions_item = $var_itemnum
                                                                    and openactions_status = 'COMPLETED';");
$closedtasks->execute();
$closedtasksarray = $closedtasks->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($closedtasksarray as $key => $value) {
    $row[] = array_values($closedtasksarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
