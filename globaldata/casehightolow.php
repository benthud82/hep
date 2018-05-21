
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_userid = $_GET['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$var_report = ($_GET['reportsel']);
$var_buildsel = intval($_GET['buildsel']);
$var_includenoncons = intval($_GET['includenoncons']);
if ($var_includenoncons == 1) {
    $sqlnoncon = ' ';
} else {
    $sqlnoncon = " and (A.SUGGESTED_TIER not in ('CSE_PFR_NONC', 'CSE_NONCON'))";
}

switch ($var_report) {  //build sql statement for report
    case 'MOVEDOWN':

        $reportsql = " (F.FLOOR is null or F.FLOOR = 'N') and A.SUGGESTED_GRID5 <> 'C_PFR' $sqlnoncon";
        $reporthaving = " OPT_CURBUILDING = $var_buildsel";
        break;

    case 'MOVEUP':
        $reportsql = "  F.FLOOR = 'Y' and F.PRIM_RES = 'P' and A.SUGGESTED_TIER like 'CSE_PFR%' $sqlnoncon";
        $reporthaving = " OPT_CURBUILDING = $var_buildsel";
        break;
}


$bayreport = $conn1->prepare("SELECT DISTINCT
                                    A.WAREHOUSE,
                                    A.ITEM_NUMBER,
                                    A.CUR_LOCATION,
                                    A.DAYS_FRM_SLE,
                                    A.AVGD_BTW_SLE,
                                    A.LMGRD5,
                                    A.LMDEEP,
                                    B.OPT_CURRBAY,
                                    A.SUGGESTED_GRID5,
                                    A.SUGGESTED_DEPTH,
                                    B.OPT_OPTBAY,
                                    A.SUGGESTED_MAX,
                                    A.SUGGESTED_MIN,
                                    cast(A.SUGGESTED_IMPMOVES * 253 as UNSIGNED),
                                    cast(A.CURRENT_IMPMOVES * 253 as UNSIGNED),
                                    cast(A.AVG_DAILY_PICK as DECIMAL(4,2)),
                                    cast(A.AVG_DAILY_UNIT as DECIMAL(4,2)),
                                    CONCAT(cast(E.SCORE_REPLENSCORE * 100 as DECIMAL(5,2)) , '%'),
                                    CONCAT(cast(E.SCORE_WALKSCORE * 100 as DECIMAL(5,2)) , '%'),
                                    CONCAT(cast(E.SCORE_TOTALSCORE * 100 as DECIMAL(5,2)) , '%'),
                                    case
                                        when
                                            A.WAREHOUSE = 3
                                                AND A.CUR_LOCATION > 'W400000'
                                        THEN
                                            2
                                        ELSE 1
                                    END AS OPT_CURBUILDING
                                FROM
                                    hep.my_npfmvc A
                                        join
                                    hep.optimalbay B ON A.WAREHOUSE = B.OPT_WHSE
                                        and A.ITEM_NUMBER = B.OPT_ITEM
                                        and A.PACKAGE_UNIT = B.OPT_PKGU
                                        and A.PACKAGE_TYPE = B.OPT_CSLS
                                        LEFT join
                                    hep.mysql_npflsm C ON C.LMWHSE = A.WAREHOUSE
                                        and C.LMITEM = A.ITEM_NUMBER
                                        and C.LMTIER = A.LMTIER
                                    LEFT    join
                                    hep.system_npfmvc D ON D.VCWHSE = A.WAREHOUSE
                                        and D.VCITEM = A.ITEM_NUMBER
                                        and D.VCPKGU = A.PACKAGE_UNIT
                                        and D.VCFTIR = A.LMTIER
                                        join
                                    hep.slottingscore E ON E.SCORE_WHSE = A.WAREHOUSE
                                        AND E.SCORE_ITEM = A.ITEM_NUMBER
                                        AND E.SCORE_PKGU = A.PACKAGE_UNIT
                                        AND E.SCORE_ZONE = A.PACKAGE_TYPE
                                     left join hep.dsl2locs on dsl2whs = A.WAREHOUSE
                                        and dsl2item = A.ITEM_NUMBER and dsl2pkgu = A.PACKAGE_UNIT
                                     LEFT JOIN
                                        hep.case_floor_locs F ON A.CUR_LOCATION = F.LOCATION
                                            and F.WHSE = A.WAREHOUSE
                                WHERE
                                    A.WAREHOUSE = $var_whse
                                        and dsl2csls is null
                                        and LMSLR not in (2,4) and PACKAGE_TYPE in ('CSE' , 'PFR')
                                        and $reportsql
                                HAVING $reporthaving
                                ORDER BY E.SCORE_REPLENSCORE ASC");
$bayreport->execute();
$bayreportarray = $bayreport->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($bayreportarray as $key => $value) {
    $row[] = array_values($bayreportarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
