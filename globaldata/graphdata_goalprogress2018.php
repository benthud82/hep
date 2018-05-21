<?php

include_once '../sessioninclude.php';
include_once '../connection/connection_details.php';

ini_set('max_execution_time', 99999);
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$result1 = $conn1->prepare("SELECT 
                                                        DATE_FORMAT(goal_movedate, '%Y-%m') as GRAPHDATE, COUNT(*) AS MOVECOUNT
                                                    FROM
                                                        hep.itemsmoved_2018goal
                                                    WHERE
                                                        goal_whse = $var_whse
                                                    GROUP BY DATE_FORMAT(goal_movedate, '%Y-%m')
                                                    ORDER BY goal_movedate ASC");
$result1->execute();



$rows = array();
$rows['name'] = 'Year-Month';
$rows1 = array();
$rows1['name'] = 'Move Count';


foreach ($result1 as $row) {
    $rows['data'][] = $row['GRAPHDATE'];
    $rows1['data'][] = $row['MOVECOUNT'] * 1;
}


$result = array();
array_push($result, $rows);
array_push($result, $rows1);



print json_encode($result);

