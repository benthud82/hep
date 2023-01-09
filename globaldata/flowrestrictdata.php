
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




$casepkgusql = $conn1->prepare("SELECT 
                                                                REST_ITEM,
                                                                CUR_LOCATION,
                                                                LMGRD5,
                                                                SUGGESTED_SLOTQTY,
                                                                SUGGESTED_MAX,
                                                               CONCAT(caseip_reviewdate, ' | ', caseip_tsmid) AS caseip_reviewdate,
                                                                CASE
                                                                    WHEN itemcomments_id > 0 THEN 'SHOW COMMENTS'
                                                                END AS COMMENTS
                                                            FROM
                                                                hep.items_restricted
                                                                    JOIN
                                                                hep.my_npfmvc ON REST_WHSE = WAREHOUSE
                                                                    AND REST_ITEM = ITEM_NUMBER
                                                                    AND PACKAGE_TYPE = 'LSE'
                                                               LEFT JOIN
                                                                hep.caseipreview ON caseip_item = REST_ITEM
                                                                    AND caseip_whse = REST_WHSE
                                                                    LEFT JOIN
                                                                hep.slotting_itemcomments ON REST_WHSE = itemcomments_whse
                                                                    AND itemcomments_item = REST_ITEM
                                                            WHERE
                                                                REST_WHSE = $var_whse
                                                                    AND (caseip_iporcase IS NULL
                                                                    OR caseip_iporcase = 'FLOWREST') $includesql; ");
$casepkgusql->execute();
$casepkgusqlarray = $casepkgusql->fetchAll(pdo::FETCH_ASSOC);





$output = array(
    "aaData" => array()
);
$row = array();

foreach ($casepkgusqlarray as $key => $value) {
    $REST_ITEM = $casepkgusqlarray[$key]['REST_ITEM'];
    $CUR_LOCATION = $casepkgusqlarray[$key]['CUR_LOCATION'];
    $LMGRD5 = $casepkgusqlarray[$key]['LMGRD5'];
    $SUGGESTED_SLOTQTY = $casepkgusqlarray[$key]['SUGGESTED_SLOTQTY'];
    $SUGGESTED_MAX = $casepkgusqlarray[$key]['SUGGESTED_MAX'];
    $caseip_reviewdate = $casepkgusqlarray[$key]['caseip_reviewdate'];
    $COMMENTS = $casepkgusqlarray[$key]['COMMENTS'];
    
    
    
    $rowpush = array(' ', $REST_ITEM, $CUR_LOCATION, $LMGRD5, $SUGGESTED_SLOTQTY, $SUGGESTED_MAX, $caseip_reviewdate, $COMMENTS);
    $row[] = array_values($rowpush);
}


$output['aaData'] = $row;
echo json_encode($output);
