<?php

include_once '../connection/connection_details.php';
$var_userid = isset($_POST['userid']) ? $_POST['userid'] : '';
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = ?");
$whssql->execute(array($var_userid));
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





$replenred_loose = $conn1->prepare("SELECT
                                SUM(N.CURRENT_IMPMOVES - N.SUGGESTED_IMPMOVES) AS REPLENREDLOOSE
                            FROM hep.my_npfmvc N
                            LEFT JOIN hep.optimalbay O
                                ON O.OPT_WHSE = N.WAREHOUSE
                                AND O.OPT_ITEM = N.ITEM_NUMBER
                                AND O.OPT_PKGU = N.PACKAGE_UNIT
                                AND O.OPT_CSLS = N.PACKAGE_TYPE
                                AND O.OPT_LEVEL = N.CUR_LEVEL
                            WHERE N.WAREHOUSE = 'HEP'
                                AND N.CURRENT_IMPMOVES > N.SUGGESTED_IMPMOVES
                                AND ((N.CURRENT_IMPMOVES - N.SUGGESTED_IMPMOVES) * 4
                                    + COALESCE(O.OPT_ADDTLFTPERDAY, 0) / 1000 / 1.4 / 60) > 0");
$replenred_loose->execute();
$replenred_loosearray = $replenred_loose->fetchAll(pdo::FETCH_ASSOC);

echo intval($replenred_loosearray[0]['REPLENREDLOOSE']) . ' Moves';




