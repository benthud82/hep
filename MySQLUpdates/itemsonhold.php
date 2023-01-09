
<?php

$JAX_ENDCAP = 0;
$whssel = 'HEP';
$data = array();
include '../connection/connection_details.php';
$itemsonhold = $conn1->prepare("SELECT DISTINCT
                                'HEP' AS WAREHOUSE,
                                A.ITEM AS ITEM_NUMBER,
                                A.PKGU AS PACKAGE_UNIT,
                                A.PKTYPE AS PACKAGE_TYPE,
                                D.slotmaster_loc AS LMLOC,
                                A.DSLS AS DAYS_FRM_SLE,
                                A.ADBS AS AVGD_BTW_SLE,
                                A.AVG_INVOH AS AVG_INV_OH,
                                A.DAYCOUNT AS NBR_SHIP_OCC,
                                A.AVG_PICK AS PICK_QTY_MN,
                                A.PICK_STD AS PICK_QTY_SD,
                                A.AVG_UNITS AS SHIP_QTY_MN,
                                A.UNIT_STD AS SHIP_QTY_SD,
                                X.CPCEPKU,
                                X.CPCCPKU,
                                X.CPCFLOW,
                                X.CPCTOTE,
                                X.CPCSHLF,
                                X.CPCROTA,
                                X.CPCESTK,
                                X.CPCLIQU,
                                X.CPCELEN,
                                X.CPCEHEI,
                                X.CPCEWID,
                                X.CPCCLEN,
                                X.CPCCHEI,
                                X.CPCCWID,
                                X.CPCNEST,
                                D.slotmaster_pickzone,
                                D.slotmaster_usehigh AS LMHIGH,
                                D.slotmaster_usedeep AS LMDEEP,
                                D.slotmaster_usewide AS LMWIDE,
                                D.slotmaster_usecube * 1000000 AS LMVOL9,
                                D.slotmaster_tier AS LMTIER,
                                D.slotmaster_dimgroup AS LMGRD5,
                                D.slotmaster_maxreplen AS CURMAX,
                                D.slotmaster_minreplen AS CURMIN,
                                CASE
                                    WHEN X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 THEN (($sql_dailyunit) * X.CPCELEN * X.CPCEHEI * X.CPCEWID)
                                    ELSE ($sql_dailyunit) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID / X.CPCCPKU
                                END AS DLY_CUBE_VEL,
                                CASE
                                    WHEN X.CPCELEN * X.CPCEHEI * X.CPCEWID > 0 THEN ($sql_dailypick) * X.CPCELEN * X.CPCEHEI * X.CPCEWID
                                    ELSE ($sql_dailypick) * X.CPCCLEN * X.CPCCHEI * X.CPCCWID
                                END AS DLY_PICK_VEL,
                                $sql_dailypick AS DAILYPICK,
                                $sql_dailyunit AS DAILYUNIT,
                             S.CASETF,
                               HOLDTIER,
                               HOLDGRID,
                               HOLDLOCATION
                             FROM
                            hep.nptsld A
                                JOIN
                            hep.npfcpcsettings X ON X.CPCITEM = A.ITEM
                            join hep.item_location on A.ITEM = loc_item
                            JOIN
                            hep.slotmaster D ON D.slotmaster_loc = loc_item
                                LEFT JOIN
                            hep.my_npfmvc F ON F.ITEM_NUMBER = A.ITEM
                                      LEFT JOIN
                            hep.item_settings S on
                                       S.ITEM = A.ITEM 
                            WHERE
                                     (HOLDLOCATION <> '') AND slotmaster_level = '$level'
                            ORDER BY DLY_CUBE_VEL desc");
$itemsonhold->execute();
$itemsonholdarray = $itemsonhold->fetchAll(pdo::FETCH_ASSOC);

foreach ($itemsonholdarray as $key => $value) {

    $ITEM_NUMBER = intval($itemsonholdarray[$key]['ITEM_NUMBER']);
    //Check OK in Shelf Setting
    $var_OKINSHLF = $itemsonholdarray[$key]['CPCSHLF'];
    $var_stacklimit = $itemsonholdarray[$key]['CPCESTK'];
    $var_casetf = $itemsonholdarray[$key]['CASETF'];
    $var_gridheight = $itemsonholdarray[$key]['LMHIGH'];
    $var_griddepth = $itemsonholdarray[$key]['LMDEEP'];
    $var_gridwidth = $itemsonholdarray[$key]['LMWIDE'];

    $var_AVGSHIPQTY = $itemsonholdarray[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($itemsonholdarray[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($itemsonholdarray[$key]['AVG_INV_OH']);

    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }
    $var_PCLIQU = $itemsonholdarray[$key]['CPCLIQU'];

    $var_PCEHEIin = $itemsonholdarray[$key]['CPCEHEI'];
    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = $itemsonholdarray[$key]['CPCCHEI'];
    }

    if ($var_PCEHEIin == 0) {
        $var_PCEHEIin = 1;
    }

    $var_PCELENin = $itemsonholdarray[$key]['CPCELEN'];
    if ($var_PCELENin == 0) {
        $var_PCELENin = $itemsonholdarray[$key]['CPCCLEN'];
    }

    if ($var_PCELENin == 0) {
        $var_PCELENin = 1;
    }

    $var_PCEWIDin = $itemsonholdarray[$key]['CPCEWID'];
    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = $itemsonholdarray[$key]['CPCCWID'];
    }

    if ($var_PCEWIDin == 0) {
        $var_PCEWIDin = 1;
    }

    $var_PCCHEIin = $itemsonholdarray[$key]['CPCCHEI'];
    $var_PCCLENin = $itemsonholdarray[$key]['CPCCLEN'];
    $var_PCCWIDin = $itemsonholdarray[$key]['CPCCWID'];

    $var_eachqty = $itemsonholdarray[$key]['CPCEPKU'];
    $var_caseqty = $itemsonholdarray[$key]['CPCCPKU'];
    if ($var_eachqty == 0) {
        $var_eachqty = 1;
    }


    if ($AVGD_BTW_SLE <= 1) {
        $daystostock = 30;
    } elseif ($AVGD_BTW_SLE <= 2) {
        $daystostock = 18;
    } elseif ($AVGD_BTW_SLE <= 3) {
        $daystostock = 13;
    } elseif ($AVGD_BTW_SLE <= 4) {
        $daystostock = 10;
    } elseif ($AVGD_BTW_SLE <= 5) {
        $daystostock = 8;
    } elseif ($AVGD_BTW_SLE <= 7) {
        $daystostock = 6;
    } elseif ($AVGD_BTW_SLE <= 10) {
        $daystostock = 4;
    } elseif ($AVGD_BTW_SLE <= 15) {
        $daystostock = 3;
    } elseif ($AVGD_BTW_SLE <= 20) {
        $daystostock = 1;
    } elseif ($AVGD_BTW_SLE <= 25) {
        $daystostock = 1;
    } elseif ($AVGD_BTW_SLE <= 30) {
        $daystostock = 1;
    } elseif ($AVGD_BTW_SLE <= 40) {
        $daystostock = 1;
    } elseif ($AVGD_BTW_SLE <= 50) {
        $daystostock = 1;
    } else {
        $daystostock = 1;
    }

//    $PKGU_PERC_Restriction = $itemsonholdarray[$key]['PERC_PERC'];
    $PKGU_PERC_Restriction = 1;

    //call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, 999999, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($itemsonholdarray[$key]['PACKAGE_UNIT']);
        $var_pkty = $itemsonholdarray[$key]['PACKAGE_TYPE'];
        $optqty = $slotqty_return_array['OPTQTY'];
        $slotqty = $slotqty_return_array['CEILQTY'];
        //write to table inventory_restricted
        $result2 = $conn1->prepare("INSERT INTO hep.inventory_restricted (ID_INV_REST, WHSE_INV_REST, ITEM_INV_REST, PKGU_INV_REST, PKGTYPE_INV_REST, AVGINV_INV_REST, OPTQTY_INV_REST, CEILQTY_INV_REST) values (0,'$whssel', $ITEM_NUMBER ,$var_pkgu,'$var_pkty',$var_AVGINV, $optqty, $slotqty)");
        $result2->execute();
    } else {
        $slotqty = $slotqty_return_array['OPTQTY'];
    }


    if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
        $slotqty = 1;
    } elseif ($slotqty == 0) {
        $slotqty = $var_AVGINV;
    }

    $var_grid5 = $itemsonholdarray[$key]['LMGRD5'];

    //Call the true fit for L04`
    if (($var_PCCHEIin * $var_PCCLENin * $var_PCCWIDin * $var_caseqty > 0)) {
        $SUGGESTED_MAX_array = _truefitgrid2iterations_case($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCCHEIin, $var_PCCLENin, $var_PCCWIDin, $var_caseqty);
    } else if ($var_stacklimit > 0) {
        $SUGGESTED_MAX_array = _truefit($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin, 0, $var_stacklimit);
    } else {
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
    }
    $SUGGESTED_MAX = $SUGGESTED_MAX_array[1];

    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

    //append data to array for writing to my_npfmvc table
    $itemsonholdarray[$key]['SUGGESTED_TIER'] = $itemsonholdarray[$key]['HOLDTIER'];
    $itemsonholdarray[$key]['SUGGESTED_GRID5'] = $itemsonholdarray[$key]['HOLDGRID'];
    $itemsonholdarray[$key]['SUGGESTED_DEPTH'] = $itemsonholdarray[$key]['LMDEEP'];
    $itemsonholdarray[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $itemsonholdarray[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $itemsonholdarray[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $itemsonholdarray[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $itemsonholdarray[$key]['SHIP_QTY_MN'], $itemsonholdarray[$key]['AVGD_BTW_SLE']);
    $itemsonholdarray[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($itemsonholdarray[$key]['CURMAX'], $itemsonholdarray[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $itemsonholdarray[$key]['SHIP_QTY_MN'], $itemsonholdarray[$key]['AVGD_BTW_SLE']);
    $itemsonholdarray[$key]['SUGGESTED_NEWLOCVOL'] = intval($itemsonholdarray[$key]['LMVOL9']);
    $itemsonholdarray[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

    //********** START of SQL to ADD TO TABLE **********
    $WAREHOUSE = ($itemsonholdarray[$key]['WAREHOUSE']);
    $ITEM_NUMBER = intval($itemsonholdarray[$key]['ITEM_NUMBER']);
    $PACKAGE_UNIT = intval($itemsonholdarray[$key]['PACKAGE_UNIT']);
    $PACKAGE_TYPE = $itemsonholdarray[$key]['PACKAGE_TYPE'];
    $CUR_LOCATION = $itemsonholdarray[$key]['LMLOC'];
    $DAYS_FRM_SLE = intval($itemsonholdarray[$key]['DAYS_FRM_SLE']);
    $AVGD_BTW_SLE = intval($itemsonholdarray[$key]['AVGD_BTW_SLE']);
    $AVG_INV_OH = intval($itemsonholdarray[$key]['AVG_INV_OH']);
    $NBR_SHIP_OCC = intval($itemsonholdarray[$key]['NBR_SHIP_OCC']);
    $PICK_QTY_MN = intval($itemsonholdarray[$key]['PICK_QTY_MN']);
    $PICK_QTY_SD = $itemsonholdarray[$key]['PICK_QTY_SD'];
    $SHIP_QTY_MN = intval($itemsonholdarray[$key]['SHIP_QTY_MN']);
    $SHIP_QTY_SD = $itemsonholdarray[$key]['SHIP_QTY_SD'];
    $CPCEPKU = intval($itemsonholdarray[$key]['CPCEPKU']);
    $CPCCPKU = intval($itemsonholdarray[$key]['CPCCPKU']);
    $CPCFLOW = $itemsonholdarray[$key]['CPCFLOW'];
    if (is_null($CPCFLOW)) {
        $CPCFLOW = ' ';
    }
    $CPCTOTE = $itemsonholdarray[$key]['CPCTOTE'];
    if (is_null($CPCTOTE)) {
        $CPCTOTE = ' ';
    }
    $CPCSHLF = $itemsonholdarray[$key]['CPCSHLF'];
    if (is_null($CPCSHLF)) {
        $CPCSHLF = ' ';
    }
    $CPCROTA = $itemsonholdarray[$key]['CPCROTA'];
    if (is_null($CPCROTA)) {
        $CPCROTA = ' ';
    }
    $CPCESTK = intval($itemsonholdarray[$key]['CPCESTK']);
    if (is_null($CPCESTK)) {
        $CPCESTK = ' ';
    }
    $CPCLIQU = $itemsonholdarray[$key]['CPCLIQU'];
    if (is_null($CPCLIQU)) {
        $CPCLIQU = ' ';
    }
    $CPCELEN = $itemsonholdarray[$key]['CPCELEN'];
    $CPCEHEI = $itemsonholdarray[$key]['CPCEHEI'];
    $CPCEWID = $itemsonholdarray[$key]['CPCEWID'];
    $CPCCLEN = $itemsonholdarray[$key]['CPCCLEN'];
    if (is_null($CPCCLEN)) {
        $CPCCLEN = 0;
    }
    $CPCCHEI = $itemsonholdarray[$key]['CPCCHEI'];
    if (is_null($CPCCHEI)) {
        $CPCCHEI = 0;
    }
    $CPCCWID = $itemsonholdarray[$key]['CPCCWID'];
    if (is_null($CPCCWID)) {
        $CPCCWID = 0;
    }
    $LMHIGH = ($itemsonholdarray[$key]['LMHIGH']);
    $LMDEEP = ($itemsonholdarray[$key]['LMDEEP']);
    $LMWIDE = ($itemsonholdarray[$key]['LMWIDE']);
    $LMVOL9 = ($itemsonholdarray[$key]['LMVOL9']);
    $LMTIER = $itemsonholdarray[$key]['LMTIER'];
    $LMGRD5 = $itemsonholdarray[$key]['LMGRD5'];
    $DLY_CUBE_VEL = intval($itemsonholdarray[$key]['DLY_CUBE_VEL']);
    $DLY_PICK_VEL = intval($itemsonholdarray[$key]['DLY_PICK_VEL']);
    $SUGGESTED_TIER = $itemsonholdarray[$key]['SUGGESTED_TIER'];
    $SUGGESTED_GRID5 = $itemsonholdarray[$key]['SUGGESTED_GRID5'];
    $SUGGESTED_DEPTH = $itemsonholdarray[$key]['SUGGESTED_DEPTH'];
    $SUGGESTED_MAX = intval($itemsonholdarray[$key]['SUGGESTED_MAX']);
    $SUGGESTED_MIN = intval($itemsonholdarray[$key]['SUGGESTED_MIN']);
    $SUGGESTED_SLOTQTY = intval($itemsonholdarray[$key]['SUGGESTED_SLOTQTY']);

    $SUGGESTED_IMPMOVES = ($itemsonholdarray[$key]['SUGGESTED_IMPMOVES']);
    $CURRENT_IMPMOVES = ($itemsonholdarray[$key]['CURRENT_IMPMOVES']);
    $SUGGESTED_NEWLOCVOL = ($itemsonholdarray[$key]['SUGGESTED_NEWLOCVOL']);
    $SUGGESTED_DAYSTOSTOCK = intval($itemsonholdarray[$key]['SUGGESTED_DAYSTOSTOCK']);
    $AVG_DAILY_PICK = $itemsonholdarray[$key]['DAILYPICK'];
    $AVG_DAILY_UNIT = $itemsonholdarray[$key]['DAILYUNIT'];
    $CUR_LEVEL = substr($CUR_LOCATION, 0, 1);

    $data[] = "('$WAREHOUSE',$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$CUR_LOCATION','$CUR_LEVEL', $DAYS_FRM_SLE,$AVGD_BTW_SLE,$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,$CPCEPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMHIGH','$LMDEEP','$LMWIDE','$LMVOL9','$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5','$SUGGESTED_DEPTH',$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES','$SUGGESTED_NEWLOCVOL',$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', $JAX_ENDCAP)";

    if ($key % 100 == 0 && $key <> 0) {
        $values = implode(',', $data);

        $sql = "INSERT IGNORE INTO hep.my_npfmvc ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $data = array();
    }
    //********** END of SQL to ADD TO TABLE **********
}

If (count($data) > 0) {
    $values = implode(',', $data);

    $sql = "INSERT IGNORE INTO hep.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $data = array();
}


//Add SQL to group assigned volume by tier for items on hold.  This will be removed from available volume going forward

$holdvolume = $conn1->prepare("SELECT 
                                                                SUGGESTED_TIER,
                                                                SUM(SUGGESTED_NEWLOCVOL) AS ASSVOL,
                                                                COUNT(*) AS ASSCOUNT
                                                            FROM
                                                                hep.my_npfmvc
                                                                    JOIN
                                                                hep.item_settings ON ITEM = ITEM_NUMBER
                                                            WHERE
                                                                PKGU_TYPE = 'EA' and CUR_LEVEL = '$level'
                                                            GROUP BY SUGGESTED_TIER");
$holdvolume->execute();
$holdvolumearray = $holdvolume->fetchAll(pdo::FETCH_ASSOC);

$holdgrid = $conn1->prepare("SELECT 
                                                            SUGGESTED_GRID5,
                                                            SUM(SUGGESTED_NEWLOCVOL) AS ASSVOL,
                                                            COUNT(*) AS ASSCOUNT
                                                        FROM
                                                                hep.my_npfmvc
                                                                    JOIN
                                                                hep.item_settings ON ITEM = ITEM_NUMBER
                                                            WHERE
                                                                PKGU_TYPE = 'EA' and CUR_LEVEL = '$level'
                                                        GROUP BY SUGGESTED_GRID5");
$holdgrid->execute();
$holdgridarray = $holdgrid->fetchAll(pdo::FETCH_ASSOC);




