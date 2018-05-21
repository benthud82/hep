
<?php

include_once '../connection/connection_details.php';
date_default_timezone_set('America/New_York');
$datetime = date('Y-m-d');
$autoid = 0;

$var_reqtsmnmodal = ($_POST['reqtsmnmodal']);
$var_completemodal = NULL;
$var_descriptionmodal = ($_POST['descriptionmodal']);
$var_commentmodal = ($_POST['commentmodal']);
$var_prioritymodal = intval($_POST['prioritymodal']);
$var_status = 'OPEN';



$columns = 'idchangelog_custaudit, changelog_reqtsm, changelog_reqdate, changelog_completedate, changelog_description, changelog_comment, changelog_status, changelog_priority';
$values = "0, '$var_reqtsmnmodal', '$datetime', NULL , '$var_descriptionmodal', '$var_commentmodal', '$var_status', $var_prioritymodal";


$sql = "INSERT INTO hep.changelog_slotting ($columns) VALUES ($values)";
$query = $conn1->prepare($sql);
$query->execute();

