
<?php


ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';
include_once '../../globalincludes/usa_asys.php';

$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = intval($whssqlarray[0]['slottingDB_users_PRIMDC']);

$sqldelete = "TRUNCATE TABLE hep.smallcase_temp";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();


$includetoggle = $_GET['includeaudit'];

if ($includetoggle == 0){
    $includesql =  ' and caseip_reviewdate IS NULL';
} else{
    $includesql = ' ';
}




$smallcasetempsql = $aseriesconn->prepare("SELECT LMITEM, 
                                                                             LMLOC#, 
                                                                             LMGRD5, 
                                                                             PCCVOL 
                                                                             FROM 
                                                                             HSIPCORDTA.NPFLSM 
                                                                             JOIN HSIPCORDTA.NPFCPC ON PCITEM = LMITEM
                                                                             WHERE  PCWHSE = 0 and LMWHSE = $var_whse and LMTIER in ('L02','L04') and PCCPKU = 1 and PCCVOL >= 7000");
$smallcasetempsql->execute();
$smallcasetemparray = $smallcasetempsql->fetchAll(pdo::FETCH_ASSOC);




$columns = 'sc_whse, sc_item, sc_location, sc_grid5, sc_volume';


$values = array();

$maxrange = 3999;
$counter = 0;
$rowcount = count($smallcasetemparray);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table
        $LMITEM = $smallcasetemparray[$counter]['LMITEM'];
        $LMLOC = $smallcasetemparray[$counter]['LMLOC#'];
        $LMGRD5 = $smallcasetemparray[$counter]['LMGRD5'];
        $PCCVOL = $smallcasetemparray[$counter]['PCCVOL'];

        $data[] = "($var_whse, $LMITEM, '$LMLOC', '$LMGRD5', $PCCVOL)";
        $counter +=1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO hep.smallcase_temp ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange +=4000;
} while ($counter <= $rowcount);



$casepkgusql = $conn1->prepare("SELECT 
                                                                sc_item,
                                                                sc_location,
                                                                sc_grid5,
                                                                sc_volume,
                                                                CONCAT(caseip_reviewdate, ' | ', caseip_tsmid) AS caseip_reviewdate,
                                                                CASE
                                                                    WHEN itemcomments_id > 0 THEN 'SHOW COMMENTS'
                                                                END AS COMMENTS
                                                            FROM
                                                                hep.smallcase_temp
                                                                    LEFT JOIN
                                                                hep.caseipreview ON caseip_item = sc_item
                                                                    AND caseip_whse = sc_whse
                                                                    LEFT JOIN
                                                                hep.slotting_itemcomments ON sc_whse = itemcomments_whse
                                                                    AND itemcomments_item = sc_item
                                                            WHERE
                                                                sc_whse = $var_whse
                                                                    AND (caseip_iporcase IS NULL
                                                                    OR caseip_iporcase = 'SMALLCASE') $includesql; ");
$casepkgusql->execute();
$casepkgusqlarray = $casepkgusql->fetchAll(pdo::FETCH_ASSOC);





$output = array(
    "aaData" => array()
);
$row = array();

foreach ($casepkgusqlarray as $key => $value) {
    $sc_item = $casepkgusqlarray[$key]['sc_item'];
    $sc_location = $casepkgusqlarray[$key]['sc_location'];
    $sc_grid5 = $casepkgusqlarray[$key]['sc_grid5'];
    $sc_volume = $casepkgusqlarray[$key]['sc_volume'];
    $caseip_reviewdate = $casepkgusqlarray[$key]['caseip_reviewdate'];
    $COMMENTS = $casepkgusqlarray[$key]['COMMENTS'];
    
    
    
    $rowpush = array(' ', $sc_item, $sc_location, $sc_grid5, $sc_volume, $caseip_reviewdate, $COMMENTS);
    $row[] = array_values($rowpush);
}


$output['aaData'] = $row;
echo json_encode($output);
