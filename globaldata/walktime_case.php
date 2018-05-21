<?php

include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

//determine if Sparks building one or two
if ($var_whse == 32) {
    $sparksbuild2filter = " >= 'W300000'";
    $var_whse = 3;
} elseif ($var_whse == 3) {
    $sparksbuild2filter = " <= 'W299999'";
} else {
    $sparksbuild2filter = " >= ' '";
}





$walkred_loose = $conn1->prepare("SELECT 
                                        (SUM(OPT_WALKCOST) / 253) / 19 as WALKTIMEREDCASE
                                    FROM
                                        hep.optimalbay
                                    WHERE
                                        OPT_WHSE = $var_whse
                                            and OPT_CSLS in ('CSE' , 'PFR')");
$walkred_loose->execute();
$walkred_loosearray = $walkred_loose->fetchAll(pdo::FETCH_ASSOC);

echo number_format($walkred_loosearray[0]['WALKTIMEREDCASE'],1) . ' Hours';




