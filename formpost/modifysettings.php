
<?php

include_once '../connection/connection_details.php';
include '../sessioninclude.php';
$var_casetf = ($_POST['casetf']);
$var_item = intval($_POST['itemmodal']);
$var_pkgu = intval($_POST['pkgu']);
$var_holdtier = ($_POST['tiermodal']);
$var_holdgrid = ($_POST['gridmodal']);
$var_holdloc = ($_POST['locationmodal']);
$var_pkgutype = 'LSE';

if($var_holdtier === "0" || is_null($var_holdtier) || $var_holdtier === 'null'){
    $var_holdtier = '';
}

if($var_holdgrid === "0" || $var_holdgrid === 'undefined'){
    $var_holdgrid = '';
}

//get DC
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = intval($whssqlarray[0]['slottingDB_users_PRIMDC']);

//Get ID of modified item settings record
$recid = $conn1->prepare("SELECT SETTINGS_ID from hep.item_settings WHERE WHSE =  $var_whse and ITEM = $var_item and PKGU =  $var_pkgu");
$recid->execute();
$recidarray = $recid->fetchAll(pdo::FETCH_ASSOC);
$id = $recidarray[0]['SETTINGS_ID'];

if (isset($id)) {
    //record already exists, update current record
    $varset = "CASETF = '$var_casetf' , HOLDTIER = '$var_holdtier',  HOLDGRID = '$var_holdgrid', HOLDLOCATION = '$var_holdloc'";
    $sql = "UPDATE hep.item_settings SET $varset WHERE SETTINGS_ID = $id;";
    $query = $conn1->prepare($sql);
    $query->execute();
} else {
    //no record exists, create new record
    $sql = "INSERT INTO hep.item_settings (SETTINGS_ID, WHSE, ITEM, PKGU, PKGU_TYPE, CASETF,  HOLDTIER, HOLDGRID, HOLDLOCATION) VALUES (0, $var_whse,  $var_item,  $var_pkgu, '$var_pkgutype', '$var_casetf', '$var_holdtier', '$var_holdgrid', '$var_holdloc');";
    $query = $conn1->prepare($sql);
    $query->execute();
}




