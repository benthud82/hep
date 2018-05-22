
<?php
$var_AVGINV = 9999999;
$JAX_ENDCAP = 0;
$slowdownsizecutoff = 99999;
$skippedkeycount = 0;
//what is total L04 volume available.  Only used for capacity constraints
//add hold grid decrimation logic from L01 file
//$L04onholdkey = array_search('L04', array_column($holdvolumearray, 'SUGGESTED_TIER')); //Find 'L06' associated key in items on hold array to subtract from available volume
//$L04key = array_search('L04', array_column($allvolumearray, 'LMTIER')); //Find 'L04' associated key
//
//if ($L04onholdkey !== FALSE) {
//    $L04Vol = ($allvolumearray[$L04key]['TIER_VOL']) - ($holdvolumearray[$L04onholdkey]['ASSVOL']);
//} else {
//    $L04Vol = ($allvolumearray[$L04key]['TIER_VOL']);
//}
//*** Step 4: L04 Designation ***
include '../connection/connection_details.php';
//Pull in available L04 Grid5s by volume ascending order

$L02GridsSQL = $conn1->prepare("SELECT 
                                                                        slotmaster_dimgroup as LMGRD5, slotmaster_distance as WALKFEET, slotmaster_usehigh as LMHIGH, slotmaster_usedeep as LMDEEP, slotmaster_usewide as LMWIDE, slotmaster_usecube as LMVOL9, COUNT(*) as GRIDCOUNT
                                                                    FROM
                                                                        hep.slotmaster
                                                                    WHERE
                                                                        slotmaster_level = '$level' AND slotmaster_tier = 'L02'
                                                                    GROUP BY slotmaster_dimgroup , slotmaster_distance , slotmaster_usehigh , slotmaster_usedeep , slotmaster_usewide , slotmaster_usecube
                                                                    ORDER BY slotmaster_distance , slotmaster_usecube");
$L02GridsSQL->execute();
$L02GridsArray = $L02GridsSQL->fetchAll(pdo::FETCH_ASSOC);


$L02sql = $conn1->prepare("SELECT DISTINCT
                                                               'HEP' AS WAREHOUSE,
                                                                A.ITEM AS ITEM_NUMBER,
                                                                A.PKGU AS PACKAGE_UNIT,
                                                                A.PKTYPE AS PACKAGE_TYPE,
                                                                D.loc_location AS LMLOC,
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
                                                                slotmaster_usehigh AS LMHIGH,
                                                                slotmaster_usedeep AS LMDEEP,
                                                                slotmaster_usewide AS LMWIDE,
                                                                slotmaster_usecube AS LMVOL9,
                                                                slotmaster_tier AS LMTIER,
                                                                slotmaster_dimgroup AS LMGRD5,
                                                                loc_truefit AS CURMAX,
                                                                loc_minqty AS CURMIN,
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
                                                                PERC_PERC,
                                                                  S.CASETF
                                                    FROM
                                                                hep.nptsld A
                                                                    JOIN
                                                                hep.npfcpcsettings X ON X.CPCITEM = A.ITEM
                                                                    JOIN
                                                                hep.item_location D ON D.loc_item = A.ITEM
                                                                    LEFT JOIN
                                                                hep.my_npfmvc F ON F.ITEM_NUMBER = A.ITEM
                                                                    LEFT JOIN
                                                                hep.item_settings S ON S.ITEM = A.ITEM
                                                                    JOIN
                                                                hep.pkgu_percent ON PERC_ITEM = A.ITEM
                                                                JOIN hep.slotmaster on slotmaster_loc = D.loc_location
                                                            WHERE
                                                                A.PKTYPE ='LSE'
                                                                    AND F.ITEM_NUMBER IS NULL
                                                                    AND slotmaster_tier IN ('L01' , 'L02', 'L04')
                                                                    AND slotmaster_level = '$level'
                                                                    AND PERC_PKGTYPE = 'LSE'
                                                                    AND A.DSLS <= 5
                                                            ORDER BY DAILYPICK DESC");
$L02sql->execute();
$L02array = $L02sql->fetchAll(pdo::FETCH_ASSOC);


//This logic needs to mimic the L01 assigment
foreach ($L02array as $key => $value) {

    $ITEM_NUMBER = intval($L02array[$key]['ITEM_NUMBER']);
//Check OK in Shelf Setting
    $var_OKINSHLF = $L02array[$key]['CPCSHLF'];

    $var_AVGSHIPQTY = $L02array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L02array[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

//    $var_AVGINV = intval($L02array[$key]['AVG_INV_OH']);
    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }

    $avgdailypickqty = $L02array[$key]['DAILYPICK'];
    $var_PCLIQU = $L02array[$key]['CPCLIQU'];
    $var_PCEHEIin = $L02array[$key]['CPCEHEI'];
    $var_PCELENin = $L02array[$key]['CPCELEN'];
    $var_PCEWIDin = $L02array[$key]['CPCEWID'];
    $var_eachqty = $L02array[$key]['CPCEPKU'];
    $PKGU_PERC_Restriction = $L02array[$key]['PERC_PERC'];

    switch ($level) {
        case 'A':
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 8;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 7;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 6;
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
            break;

        case 'B':
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 27;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 19;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 13;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 6;
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
            break;

        case 'C':
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 30;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 6;
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
            break;

        default:
            break;
    }

//call slot quantity logic
    $slotqty_return_array = _slotqty_offsys($var_AVGSHIPQTY, $daystostock, $var_AVGINV, $slowdownsizecutoff, $AVGD_BTW_SLE, $PKGU_PERC_Restriction);

    if (isset($slotqty_return_array['CEILQTY'])) {
        $var_pkgu = intval($L02array[$key]['PACKAGE_UNIT']);
        $var_pkty = $L02array[$key]['PACKAGE_TYPE'];
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



//calculate total slot valume to determine what grid to start
    $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;
    $var_casetf = $L02array[$key]['CASETF'];
    $var_stacklimit = $L02array[$key]['CPCESTK'];



    //loop through available L05 grids to determine smallest location to accomodate slot quantity
    foreach ($L02GridsArray as $key2 => $value) {
        $var_grid5 = $L02GridsArray[$key2]['LMGRD5'];
        $var_gridheight = $L02GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L02GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L02GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L02GridsArray[$key2]['LMVOL9'];

        //Call the case true fit for L04
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        if ($SUGGESTED_MAX_test >= $slotqty) {
            $L02GridsArray[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($L02GridsArray[$key2]['GRIDCOUNT'] <= 0) {
                unset($L02GridsArray[$key2]);
                $L02GridsArray = array_values($L02GridsArray);  //reset array
            }
            break;
        }
    }

    if ($SUGGESTED_MAX_test < $slotqty) {
        $skippedkeycount += 1;
        unset($L02array[$key]);
        continue;
    }

    $SUGGESTED_MAX = $SUGGESTED_MAX_test;

//Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

//append data to array for writing to my_npfmvc table
    $L02array[$key]['SUGGESTED_TIER'] = 'L02';
    $L02array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $L02array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L02array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L02array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L02array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L02array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
    $L02array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L02array[$key]['CURMAX'], $L02array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L02array[$key]['SHIP_QTY_MN'], $L02array[$key]['AVGD_BTW_SLE']);
    $L02array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L02array[$key]['SUGGESTED_DAYSTOSTOCK'] = $daystostock;
    $L02array[$key]['PPI'] = number_format($L02array[$key]['DAILYPICK'] / $L02array[$key]['SUGGESTED_NEWLOCVOL'], 10);


    if (count($L02GridsArray) <= 0) {
        break;
    }
}

//delete unassigned items from array using $key as the last offset
array_splice($L02array, ($key - $skippedkeycount));

$L02array = array_values($L02array);  //reset array
//sort by PPI descending
$sort = array();
foreach ($L02array as $k => $v) {
    $sort['PPI'][$k] = $v['PPI'];
    $sort['SUGGESTED_NEWLOCVOL'][$k] = $v['SUGGESTED_NEWLOCVOL'];
}
array_multisort($sort['PPI'], SORT_DESC, $sort['SUGGESTED_NEWLOCVOL'], SORT_ASC, $L02array);





//********** START of SQL to ADD TO TABLE **********
$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($L02array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = ($L02array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L02array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L02array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L02array[$counter]['PACKAGE_TYPE'];
        $CUR_LOCATION = $L02array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L02array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = ($L02array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($L02array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($L02array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($L02array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $L02array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($L02array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $L02array[$counter]['SHIP_QTY_SD'];
        $CPCEPKU = intval($L02array[$counter]['CPCEPKU']);
        $CPCCPKU = intval($L02array[$counter]['CPCCPKU']);
        $CPCFLOW = $L02array[$counter]['CPCFLOW'];
        if (is_null($CPCFLOW)) {
            $CPCFLOW = ' ';
        }
        $CPCTOTE = $L02array[$counter]['CPCTOTE'];
        if (is_null($CPCTOTE)) {
            $CPCTOTE = ' ';
        }
        $CPCSHLF = $L02array[$counter]['CPCSHLF'];
        if (is_null($CPCSHLF)) {
            $CPCSHLF = ' ';
        }
        $CPCROTA = $L02array[$counter]['CPCROTA'];
        if (is_null($CPCROTA)) {
            $CPCROTA = ' ';
        }
        $CPCESTK = intval($L02array[$counter]['CPCESTK']);
        if (is_null($CPCESTK)) {
            $CPCESTK = ' ';
        }
        $CPCLIQU = $L02array[$counter]['CPCLIQU'];
        if (is_null($CPCLIQU)) {
            $CPCLIQU = ' ';
        }
        $CPCELEN = $L02array[$counter]['CPCELEN'];
        $CPCEHEI = $L02array[$counter]['CPCEHEI'];
        $CPCEWID = $L02array[$counter]['CPCEWID'];
         $CPCCLEN = $L02array[$counter]['CPCCLEN'];
        if (is_null($CPCCLEN)) {
            $CPCCLEN = 0;
        }
        $CPCCHEI = $L02array[$counter]['CPCCHEI'];
        if (is_null($CPCCHEI)) {
            $CPCCHEI = 0;
        }
        $CPCCWID = $L02array[$counter]['CPCCWID'];
        if (is_null($CPCCWID)) {
            $CPCCWID = 0;
        }
        $LMHIGH = ($L02array[$counter]['LMHIGH']);
        $LMDEEP = ($L02array[$counter]['LMDEEP']);
        $LMWIDE = ($L02array[$counter]['LMWIDE']);
        $LMVOL9 = ($L02array[$counter]['LMVOL9']);
        $LMTIER = $L02array[$counter]['LMTIER'];
        $LMGRD5 = $L02array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $L02array[$counter]['DLY_CUBE_VEL'];
        $DLY_PICK_VEL = $L02array[$counter]['DLY_PICK_VEL'];
        $SUGGESTED_TIER = $L02array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $L02array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $L02array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($L02array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($L02array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($L02array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($L02array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($L02array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = ($L02array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($L02array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $L02array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $L02array[$counter]['DAILYUNIT'];


        $CUR_LEVEL = substr($CUR_LOCATION, 0, 1);
        $data[] = "('$WAREHOUSE',$ITEM_NUMBER,$PACKAGE_UNIT,'$PACKAGE_TYPE','$CUR_LOCATION','$CUR_LEVEL',$DAYS_FRM_SLE,'$AVGD_BTW_SLE',$AVG_INV_OH,$NBR_SHIP_OCC,$PICK_QTY_MN,$PICK_QTY_SD,$SHIP_QTY_MN,$SHIP_QTY_SD,$CPCEPKU,$CPCCPKU,'$CPCFLOW','$CPCTOTE','$CPCSHLF','$CPCROTA',$CPCESTK,'$CPCLIQU',$CPCELEN,$CPCEHEI,$CPCEWID,$CPCCLEN,$CPCCHEI,$CPCCWID,'$LMHIGH','$LMDEEP','$LMWIDE','$LMVOL9','$LMTIER','$LMGRD5',$DLY_CUBE_VEL,$DLY_PICK_VEL,'$SUGGESTED_TIER','$SUGGESTED_GRID5','$SUGGESTED_DEPTH',$SUGGESTED_MAX,$SUGGESTED_MIN,$SUGGESTED_SLOTQTY,'$SUGGESTED_IMPMOVES','$CURRENT_IMPMOVES','$SUGGESTED_NEWLOCVOL',$SUGGESTED_DAYSTOSTOCK,'$AVG_DAILY_PICK','$AVG_DAILY_UNIT', $JAX_ENDCAP)";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }

    $sql = "INSERT IGNORE INTO hep.my_npfmvc ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();

    $maxrange += 1000;
} while ($counter <= $rowcount);
