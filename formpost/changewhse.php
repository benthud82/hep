
<?php

include_once '../connection/connection_details.php';


$var_newwhse = ($_POST['newwhse']);
$var_userid = ($_POST['userid']);

$sql = "UPDATE hep.slottingdb_users SET slottingDB_users_PRIMDC = $var_newwhse WHERE idslottingDB_users_ID = '$var_userid';";
$query = $conn1->prepare($sql);
$query->execute();

