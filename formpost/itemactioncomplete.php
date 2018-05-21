
<?php

include_once '../connection/connection_details.php';
include '../sessioninclude.php';
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

date_default_timezone_set('America/New_York');
$datetime = date('Y-m-d H:i:s');

$var_commentmodal = ($_POST['commentmodal']);

$var_assigntask_id = intval($_POST['assigntask_id']);


//update completed item task table and mark status as 'COMPLETE'


$sql = "UPDATE hep.slottingdb_itemactions SET openactions_completedcomment= '$var_commentmodal', openactions_completeduser = '$var_userid', openactions_status = 'COMPLETED', openactions_completeddate = '$datetime' WHERE openactions_id = $var_assigntask_id;";
$query = $conn1->prepare($sql);
$query->execute();


