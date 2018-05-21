<?php

include_once '../connection/connection_details.php';
$var_userid = strtoupper($_GET['userid']);
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$allopentasks = $conn1->prepare("SELECT 
                                                                openactions_id,
                                                                openactions_assignedby,
                                                                openactions_assignedto,
                                                                openactions_item,
                                                                CUR_LOCATION,
                                                                date(openactions_assigneddate),
                                                                openactions_comment,
                                                                ' '
                                                            FROM
                                                                hep.slottingdb_itemactions
                                                                    LEFT JOIN
                                                                my_npfmvc ON WAREHOUSE = openactions_whse
                                                                    AND ITEM_NUMBER = openactions_item
                                                            WHERE
                                                               openactions_assignedto <> '$var_userid'
                                                                    AND PACKAGE_TYPE = 'LSE'
                                                                    AND openactions_status = 'OPEN';");
$allopentasks->execute();
$allopentasksarray= $allopentasks->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($allopentasksarray as $key => $value) {
    $row[] = array_values($allopentasksarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
