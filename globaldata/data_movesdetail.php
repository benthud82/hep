<?php

include_once '../sessioninclude.php';
include_once '../connection/connection_details.php';
include_once '../../globalincludes/usa_asys.php';
include_once '../../globalfunctions/custdbfunctions.php';

ini_set('max_execution_time', 99999);
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


$startdate = $_GET['startdate'];
$enddate = $_GET['enddate'];
$movetype = $_GET['movetype'];

$startdate_convert = intval(_datetoyyyymmdd($startdate));
$enddate_convert = intval(_datetoyyyymmdd($enddate));


if ($movetype == 'ASOs') {
    $typeconvert = "'SK', 'SP', 'SO', 'SJ'";
} elseif ($movetype == 'AUTOs') {
    $typeconvert = "'RS'";
} else {
    $typeconvert = "'SK', 'SP', 'SO', 'SJ','RS'";
}


$movedata = $aseriesconn->prepare("SELECT MVTITM,
                                                                    MVFLC#, 
                                                                    MVTLC#, 
                                                                    MVTYPE, 
                                                                    MVTICK, 
                                                                    date(substr(MVREQD,1,4) || '-' || substr(MVREQD,5,2) || '-' || substr(MVREQD,7,2)) as REQDATE, 
                                                                    MVREQT, MVREQQ, date(substr(MVCNFD,1,4) || '-' || substr(MVCNFD,5,2) || '-' || substr(MVCNFD,7,2)) as CNFDATE, 
                                                                    MVCNFT, 
                                                                    MVCNFQ  
                                                                FROM A.HSIPCORDTA.NPFMVE 
                                                                WHERE MVWHSE =  $var_whse and 
                                                                    (MVTPKG <> 0) and 
                                                                    MVCNFQ <> 0 and 
                                                                    (MVDESC like 'COMPLETED%' or MVDESC like 'MAN%') 
                                                                    and (MVCNFD between $startdate_convert and $enddate_convert)
                                                                    and MVTYPE in ($typeconvert)");
$movedata->execute();
$movedata_array = $movedata->fetchAll(pdo::FETCH_ASSOC);



$output = array(
    "aaData" => array()
);
$row = array();

foreach ($movedata_array as $key => $value) {
    $row[] = array_values($movedata_array[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
