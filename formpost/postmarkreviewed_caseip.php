
<?php

include_once '../connection/connection_details.php';
//date_default_timezone_set('America/New_York');
$datetime = date('Y-m-d');
$autoid = 0;

$itemnum = intval($_POST['itemnum']);
$userid = ($_POST['userid']);
$caseorip = ($_POST['caseorip']);

$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['slottingDB_users_PRIMDC']);




$columns = 'caseip_id, caseip_whse, caseip_item, caseip_iporcase, caseip_tsmid, caseip_reviewdate';
$values = "0, $var_whse, $itemnum, '$caseorip', '$userid' , '$datetime'";


$sql = "INSERT INTO hep.caseipreview ($columns) VALUES ($values)";
$query = $conn1->prepare($sql);
$query->execute();

