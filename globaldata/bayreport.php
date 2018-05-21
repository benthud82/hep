
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_bay = intval($_GET['baynum']);
$var_report = ($_GET['reportsel']);
$var_tier = ($_GET['tiersel']);
$var_grid5sel = $_GET['grid5sel'];
$var_levelsel = $_GET['levelsel'];

$var_returncount = ($_GET['returncount']);

switch ($var_report) {  //build sql statement for report
    case 'MOVEIN':
        if ($var_tier == 'L01') {
            $reportsql = " A.SUGGESTED_TIER = 'L01' and A.LMTIER <> 'L01' ";
        } else if ($var_tier == 'L02') {
            $reportsql = " A.SUGGESTED_TIER = 'L02' and A.LMTIER <> 'L02' ";
        } else {
            $reportsql = " B.OPT_OPTWALKFEET = $var_bay and B.OPT_CURRWALKFEET <> $var_bay  and A.SUGGESTED_GRID5 like '$var_grid5sel' and A.SUGGESTED_TIER in ('$var_tier')   ";
        }
        break;

    case 'MOVEOUT':
        if ($var_tier == 'L01') {
            $reportsql = " A.SUGGESTED_TIER <> 'L01' and A.LMTIER = 'L01' ";
        } else if ($var_tier == 'L02') {
            $reportsql = " A.SUGGESTED_TIER <> 'L02' and A.LMTIER = 'L02' ";
        } else {
            $reportsql = " B.OPT_OPTWALKFEET <> $var_bay and B.OPT_CURRWALKFEET = $var_bay  and A.LMGRD5  like '$var_grid5sel' and A.SUGGESTED_TIER  in ('$var_tier') and A.LMTIER <> 'L06' ";
        }
        break;

    case 'CURRENT':
        $reportsql = " B.OPT_CURRWALKFEET = $var_bay";
        break;

    case 'SHOULD':
        $reportsql = " B.OPT_OPTWALKFEET = $var_bay";
        break;
}


$bayreport = $conn1->prepare("SELECT 
                                                        A.WAREHOUSE,
                                                        A.ITEM_NUMBER,
                                                        A.CUR_LOCATION,
                                                        A.DAYS_FRM_SLE,
                                                        A.AVGD_BTW_SLE,
                                                        A.LMGRD5,
                                                        A.LMDEEP,
                                                        B.OPT_CURRWALKFEET,
                                                        A.SUGGESTED_GRID5,
                                                        A.SUGGESTED_DEPTH,
                                                        B.OPT_OPTWALKFEET,
                                                        A.SUGGESTED_MAX,
                                                        A.SUGGESTED_MIN,
                                                        cast(A.SUGGESTED_IMPMOVES * 253 as UNSIGNED),
                                                        cast(A.CURRENT_IMPMOVES * 253 as UNSIGNED),
                                                        cast(A.AVG_DAILY_PICK as DECIMAL(4,2)),
                                                        cast(A.AVG_DAILY_UNIT as DECIMAL(4,2)),
                                                        CONCAT(cast(E.SCORE_REPLENSCORE * 100 as DECIMAL(5,2)) , '%'),
                                                        CONCAT(cast(E.SCORE_WALKSCORE * 100 as DECIMAL(5,2)) , '%'),
                                                        CONCAT(cast(E.SCORE_TOTALSCORE * 100 as DECIMAL(5,2)) , '%')
                                                    FROM
                                                        hep.my_npfmvc A
                                                            join
                                                        hep.optimalbay B ON A.ITEM_NUMBER = B.OPT_ITEM
                                                            join
                                                        hep.slottingscore E ON E.SCORE_ITEM = A.ITEM_NUMBER
                                                    WHERE $reportsql and CUR_LEVEL = '$var_levelsel'
                                                    ORDER BY E.SCORE_REPLENSCORE ASC LIMIT $var_returncount");
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
