<?php

//get whse for user
if (isset($_SESSION['MYUSER'])) {
    $var_userid = strtoupper($_SESSION['MYUSER']);
    $whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

    $maperror = $conn1->prepare("SELECT 
                                                            COUNT(*) AS maperrorcount
                                                        FROM
                                                            hep.vectormaperrors ");
    $maperror->execute();
    $maperrorarray = $maperror->fetchAll(pdo::FETCH_ASSOC);
}
if (isset($maperrorarray)) {
    $maperrorcount = $maperrorarray[0]['maperrorcount'];
} else {
    $maperrorcount = 0;
}