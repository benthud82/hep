<?php

//get whse for user
if (isset($_SESSION['MYUSER'])) {
    $var_userid = strtoupper($_SESSION['MYUSER']);
    $whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    // Local/test users may not have a slottingdb_users row. The badge query is
    // warehouse-independent, so avoid emitting a page-level warning in that case.
    $var_whse = isset($whssqlarray[0]['slottingDB_users_PRIMDC'])
        ? $whssqlarray[0]['slottingDB_users_PRIMDC']
        : null;

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
