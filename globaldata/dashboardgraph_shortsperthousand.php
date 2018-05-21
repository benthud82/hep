<?php
ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';
$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];
$table = ($var_whse) . 'invlinesshipped';
$table2 = ($var_whse) . 'dailyshortcount';


$time = strtotime("-1 year", time());
$date = date("Y-m-d", $time);

$result1 = $conn1->prepare("SELECT 
                                INVDATE,
                                ShortCount / (INVLINES / 1000) as SHORTCOUNT
                            FROM
                                {$table}
                                JOIN
                                 {$table2} ON ShortDate = INVDATE
                            LEFT JOIN hep.excl_replenperthousand on replenexcl_whse = INVWHSE and replenexcl_date = INVDATE
                            WHERE INVDATE >= '$date' and replenexcl_date is null
                            GROUP BY INVDATE");
$result1->execute();



$rows = array();
$rows['name'] = 'Date';
$rows1 = array();
$rows1['name'] = 'Shorts per 1000';


foreach ($result1 as $row) {
    $rows['data'][] = $row['INVDATE'];
    $rows1['data'][] = ($row['SHORTCOUNT']) * 1.0;

}


$result = array();
array_push($result, $rows);
array_push($result, $rows1);


print json_encode($result);
