
<?php


ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['slottingDB_users_PRIMDC']);


$includetoggle = $_GET['includeaudit'];

if ($includetoggle == 0){
    $includesql =  ' and caseip_reviewdate IS NULL';
} else{
    $includesql = ' ';
}


$casepkgusql = $conn1->prepare("SELECT DISTINCT
                                                                VCITEM,
                                                                VCLOC,
                                                                VCPKGU,
                                                                VCGRD5,
                                                                CASEPKGU,
                                                                TOTOPP,
                                                                CONCAT(caseip_reviewdate, ' | ', caseip_tsmid) AS caseip_reviewdate,
                                                                CASE
                                                                    WHEN itemcomments_id > 0 THEN 'SHOW COMMENTS'
                                                                END AS COMMENTS
                                                            FROM
                                                                hep.caseopps
                                                                    LEFT JOIN
                                                                hep.caseipreview ON caseip_item = VCITEM
                                                                    AND caseip_whse = VCWHSE
                                                                    LEFT JOIN
                                                                hep.slotting_itemcomments ON VCWHSE = itemcomments_whse
                                                                    AND itemcomments_item = VCITEM
                                                            WHERE
                                                                VCWHSE = $var_whse and (caseip_iporcase IS NULL or caseip_iporcase =  'CASE') $includesql; ");
$casepkgusql->execute();
$casepkgusqlarray = $casepkgusql->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($casepkgusqlarray as $key => $value) {
    $VCITEM = $casepkgusqlarray[$key]['VCITEM'];
    $VCLOC = $casepkgusqlarray[$key]['VCLOC'];
    $VCPKGU = $casepkgusqlarray[$key]['VCPKGU'];
    $VCGRD5 = $casepkgusqlarray[$key]['VCGRD5'];
    $IPPKGU = $casepkgusqlarray[$key]['CASEPKGU'];
    $TOTOPP = $casepkgusqlarray[$key]['TOTOPP'];
    $caseip_reviewdate = $casepkgusqlarray[$key]['caseip_reviewdate'];
    $COMMENTS = $casepkgusqlarray[$key]['COMMENTS'];
    
    
    
    $rowpush = array(' ', $VCITEM, $VCLOC, $VCPKGU, $VCGRD5, $IPPKGU, $TOTOPP,$caseip_reviewdate, $COMMENTS );
    $row[] = array_values($rowpush);
}


$output['aaData'] = $row;
echo json_encode($output);
