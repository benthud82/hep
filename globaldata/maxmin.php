
<?php

ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';
include_once '../../globalfunctions/slottingfunctions.php';
include_once '../../globalfunctions/newitem.php';


$var_report = ($_GET['reportsel']);

switch ($var_report) {  //build sql statement for report
    case 'MAX':

        $bayreport = $conn1->prepare("SELECT DISTINCT
                                        A.WAREHOUSE,
                                        A.ITEM_NUMBER,
                                        A.CUR_LOCATION,
                                        loc_truefit as CURMAX,
                                        loc_minqty as CURMIN,
                                        CURRENT_IMPMOVES,
                                        CURTF as SUGGSTEDMAX,
                                        SHIP_QTY_MN,
                                        AVG_DAILY_UNIT,
                                        AVG_INV_OH,
                                        AVGD_BTW_SLE,
                                        concat(minmax_reviewdate, ' | ', minmax_tsmid) as minmax_reviewdate,
                                        case when itemcomments_id > 0 then 'SHOW COMMENTS'  end as COMMENTS, 
                                        CASETF,
                                        A.LMGRD5,
                                        CPCCLEN as CPCCLEN,
                                        CPCCHEI as CPCCHEI,
                                        CPCCWID as  CPCCWID,
                                        A.LMDEEP,
                                        A.LMHIGH,
                                        A.LMWIDE,
                                        A.CPCCPKU
                                    FROM
                                        hep.my_npfmvc A
                                        JOIN hep.item_location on loc_item = ITEM_NUMBER
                                            LEFT JOIN
                                        hep.minmaxreview ON minmax_item = ITEM_NUMBER
                                            AND minmax_location = CUR_LOCATION
                                    LEFT JOIN hep.slotting_itemcomments on WAREHOUSE = itemcomments_whse and itemcomments_item = ITEM_NUMBER
                                   LEFT JOIN
                                hep.item_settings S on S.WHSE = A.WAREHOUSE 
                                      and S.ITEM = A.ITEM_NUMBER 
                                      and S.PKGU = A.PACKAGE_UNIT 
                                      and S.PKGU_TYPE = A.PACKAGE_TYPE
                                    WHERE
                                        AND SUGGESTED_MAX > CURMAX
                                            and CURMAX < CURTF
                                            AND A.LMTIER IN ('L02' , 'L04')
                                            and CURRENT_IMPMOVES >= .1");
        $bayreport->execute();
        $bayreportarray = $bayreport->fetchAll(pdo::FETCH_ASSOC);
        break;

    case 'MIN':
        //need to add SQL statement
        break;
}


foreach ($bayreportarray as $key => $value) {

    $WHSE = $bayreportarray[$key]['WAREHOUSE'];
    $ITEM_NUMBER = $bayreportarray[$key]['ITEM_NUMBER'];
    $CUR_LOCATION = $bayreportarray[$key]['CUR_LOCATION'];
    $CURMAX = $bayreportarray[$key]['CURMAX'];
    $CURMIN = $bayreportarray[$key]['CURMIN'];
    $var_grid5 = $bayreportarray[$key]['LMGRD5'];
    $var_PCCHEIin = $bayreportarray[$key]['CPCCHEI'];
    $var_PCCLENin = $bayreportarray[$key]['CPCCLEN'];
    $var_PCCWIDin = $bayreportarray[$key]['CPCCWID'];
    $var_gridheight = $bayreportarray[$key]['LMHIGH'];
    $var_griddepth = $bayreportarray[$key]['LMDEEP'];
    $var_gridwidth = $bayreportarray[$key]['LMWIDE'];
    $var_caseqty = $bayreportarray[$key]['CPCCPKU'];
    $var_PCLIQU = '  ';
    $var_casetf = $bayreportarray[$key]['CASETF'];

    if ($var_casetf == 'Y' && substr($var_grid5, 2, 1) == 'S' && ($var_PCCHEIin * $var_PCCLENin * $var_PCCWIDin * $var_caseqty > 0 )) {
        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCCHEIin, $var_PCCLENin, $var_PCCWIDin, $var_caseqty);
        $SUGGSTEDMAX = $SUGGESTED_MAX_array[1];
    } else {
        $SUGGSTEDMAX = $bayreportarray[$key]['SUGGSTEDMAX'];
    }


    $CURRENT_IMPMOVES = intval($bayreportarray[$key]['CURRENT_IMPMOVES'] * 253);
    $SHIP_QTY_MN = $bayreportarray[$key]['SHIP_QTY_MN'];
    $CPCCPKU = 1;
    $AVG_DAILY_UNIT = $bayreportarray[$key]['AVG_DAILY_UNIT'];
    $AVG_INV_OH = $bayreportarray[$key]['AVG_INV_OH'];
    $AVGD_BTW_SLE = $bayreportarray[$key]['AVGD_BTW_SLE'];
    $minmax_reviewdate = $bayreportarray[$key]['minmax_reviewdate'];
    $COMMENTS = $bayreportarray[$key]['COMMENTS'];

    $newmin = ceil(_minloc($SUGGSTEDMAX, $AVG_DAILY_UNIT, $CPCCPKU));
    $newimpliedmoves = intval(_implied_daily_moves($SUGGSTEDMAX, $newmin, $AVG_DAILY_UNIT, $AVG_INV_OH, $SHIP_QTY_MN, $AVGD_BTW_SLE) * 253);
    $replenreduction = intval($CURRENT_IMPMOVES - $newimpliedmoves);
    $bayreportarray[$key]['SUGGSTEDMIN'] = $newmin;
    $bayreportarray[$key]['IMP_IMPMOVES'] = $newimpliedmoves;
    $bayreportarray[$key]['REPLENRED'] = $replenreduction;
    $bayreportarray[$key]['CURRENT_IMPMOVES'] = $CURRENT_IMPMOVES;
    unset($bayreportarray[$key]['SHIP_QTY_MN']);
    unset($bayreportarray[$key]['AVG_DAILY_UNIT']);
    unset($bayreportarray[$key]['AVG_INV_OH']);
    unset($bayreportarray[$key]['AVGD_BTW_SLE']);


    if (($newimpliedmoves - $CURRENT_IMPMOVES) <= -.1) {

        $rowpush = array(' ', $WHSE, $ITEM_NUMBER, $CUR_LOCATION, $CURMAX, $CURMIN, $CURRENT_IMPMOVES, $SUGGSTEDMAX, $newmin, $newimpliedmoves, $replenreduction, $minmax_reviewdate, $COMMENTS,);
        $row[] = array_values($rowpush);
    }
}





$output = array(
    "aaData" => array()
);



$output['aaData'] = $row;
echo json_encode($output);
