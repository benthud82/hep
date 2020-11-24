
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_report = ($_GET['reportsel']);
$var_level = ($_GET['levelsel']);



switch ($var_report) {  //build sql statement for report
    case 'highwalk':

        $whercaluse = ' B.OPT_ADDTLFTPERDAY > 0 ';
        $orderby = ' B.OPT_ADDTLFTPERDAY DESC';
        break;

    case 'negativewalk':

        $whercaluse = ' B.OPT_ADDTLFTPERDAY < 0';
        $orderby = ' B.OPT_ADDTLFTPERDAY ASC';
        break;
}


$dopoundsql = $conn1->prepare("SELECT DISTINCT
                                                                A.WAREHOUSE,
                                                                A.ITEM_NUMBER,
                                                                A.CUR_LOCATION,
                                                                B.OPT_ADDTLFTPERDAY,
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
                                                                CAST(A.SUGGESTED_IMPMOVES * 253 AS UNSIGNED),
                                                                CAST(A.CURRENT_IMPMOVES * 253 AS UNSIGNED),
                                                                CAST(A.AVG_DAILY_PICK AS DECIMAL (4 , 2 )),
                                                                CAST(A.AVG_DAILY_UNIT AS DECIMAL (4 , 2 )),
                                                                CONCAT(CAST(E.SCORE_REPLENSCORE * 100 AS DECIMAL (5 , 2 )),
                                                                        '%'),
                                                                CONCAT(CAST(E.SCORE_WALKSCORE * 100 AS DECIMAL (5 , 2 )),
                                                                        '%'),
                                                                CONCAT(CAST(E.SCORE_TOTALSCORE * 100 AS DECIMAL (5 , 2 )),
                                                                        '%'),
                                                                (SELECT 
                                                                        COUNT(*) AS LOC_COUNT
                                                                    FROM
                                                                        hep.item_location
                                                                    WHERE
                                                                        loc_item = A.ITEM_NUMBER) AS LOC_COUNT
                                                            FROM
                                                                hep.my_npfmvc A
                                                                    JOIN
                                                                hep.optimalbay B ON A.ITEM_NUMBER = B.OPT_ITEM
                                                                   JOIN
                                                                hep.slottingscore E ON E.SCORE_ITEM = A.ITEM_NUMBER
                                                                JOIN hep.slotmaster on slotmaster_loc = CUR_LOCATION
                                                                WHERE
                                                                A.SUGGESTED_TIER = 'L04'
                                                                    and PACKAGE_TYPE =  'LSE'
                                                                    and CUR_LEVEL = '$var_level'
                                                                        and A.DAYS_FRM_SLE <= 25
                                                                         and (slotmaster_locdesc NOT LIKE ('GS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('WK%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('VS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('KH%'))
                                                                    AND $whercaluse
                                                                    ORDER BY $orderby");
$dopoundsql->execute();
$dopoundsqlarray = $dopoundsql->fetchAll(pdo::FETCH_ASSOC);


$output = array(
    "aaData" => array()
);
$row = array();

foreach ($dopoundsqlarray as $key => $value) {
    $row[] = array_values($dopoundsqlarray[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
