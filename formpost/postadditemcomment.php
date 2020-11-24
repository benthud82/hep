
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

$var_descriptionmodal = ($_POST['descriptionmodal']);
$var_commentmodal = ($_POST['commentmodal']);
$var_itemmodal = ($_POST['itemmodal']);



$columns = 'itemcomments_id, itemcomments_whse, itemcomments_item, itemcomments_tsm, itemcomments_date, itemcomments_header, itemcomments_comment';
$values = "0, '$var_whse', $var_itemmodal, '$var_userid' , '$datetime', '$var_descriptionmodal', '$var_commentmodal'";


$sql = "INSERT INTO hep.slotting_itemcomments ($columns) VALUES ($values)";
$query = $conn1->prepare($sql);
$query->execute();

