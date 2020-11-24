
<?php
$var_AVGINV = 9999999;
$JAX_ENDCAP = 0;
$slowdownsizecutoff = 99999;

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

$L04GridsSQL = $conn1->prepare("SELECT 
                                                                        slotmaster_dimgroup as LMGRD5,  slotmaster_usehigh as LMHIGH, slotmaster_usedeep as LMDEEP, slotmaster_usewide as LMWIDE, slotmaster_usecube as LMVOL9, COUNT(*) as GRIDCOUNT
                                                                    FROM
                                                                        hep.slotmaster
                                                                    WHERE
                                                                        slotmaster_level = '$level' AND slotmaster_tier = 'L04' and (slotmaster_locdesc NOT LIKE ('GS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('WK%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('VS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('KH%'))
                                                                    GROUP BY slotmaster_dimgroup ,  slotmaster_usehigh , slotmaster_usedeep , slotmaster_usewide , slotmaster_usecube
                                                                    ORDER BY  slotmaster_usecube");
$L04GridsSQL->execute();
$L04GridsArray = $L04GridsSQL->fetchAll(pdo::FETCH_ASSOC);

$L04GridsArray_count = count($L04GridsArray);

$L04sql = $conn1->prepare("SELECT DISTINCT
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
                                                                    LEFT JOIN
                                                                hep.pkgu_percent ON PERC_ITEM = A.ITEM
                                                                JOIN hep.slotmaster on slotmaster_loc = D.loc_location
                                                                JOIN hep.item_maxvol on A.ITEM = maxvol_item
                                                            WHERE
                                                                A.PKTYPE ='LSE'
                                                                    AND F.ITEM_NUMBER IS NULL
                                                                    AND slotmaster_tier IN ('L01' , 'L02', 'L04', 'L06')
                                                                    AND slotmaster_level = '$level'
                                                                    AND (PERC_PKGTYPE = 'LSE' or PERC_PKGTYPE is null)
                                                                    and slotmaster_usecube = maxvol_vol
                                                                    and (slotmaster_locdesc NOT LIKE ('GS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('WK%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('VS%')
                                                                                    AND slotmaster_locdesc NOT LIKE ('KH%'))
                                                                     and slotmaster_block not in ('S', 'N')
                                                            ORDER BY DLY_CUBE_VEL DESC, DAILYPICK desc");
$L04sql->execute();
$L04array = $L04sql->fetchAll(pdo::FETCH_ASSOC);


//This logic needs to mimic the L01 assigment
foreach ($L04array as $key => $value) {

    $ITEM_NUMBER = intval($L04array[$key]['ITEM_NUMBER']);

//Check OK in Shelf Setting
    $var_OKINSHLF = $L04array[$key]['CPCSHLF'];

    $var_AVGSHIPQTY = $L04array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L04array[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

//    $var_AVGINV = intval($L04array[$key]['AVG_INV_OH']);
    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }

    $avgdailypickqty = $L04array[$key]['DAILYPICK'];
    $var_PCLIQU = $L04array[$key]['CPCLIQU'];
    $var_PCEHEIin = $L04array[$key]['CPCEHEI'];
    $var_PCELENin = $L04array[$key]['CPCELEN'];
    $var_PCEWIDin = $L04array[$key]['CPCEWID'];
    $var_eachqty = $L04array[$key]['CPCEPKU'];
    $PKGU_PERC_Restriction = $L04array[$key]['PERC_PERC'];

    switch ($level) {
        case 'A':
            if ($AVGD_BTW_SLE <= 1) {
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 8;
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
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 8;
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
                $daystostock = 20;
            } elseif ($AVGD_BTW_SLE <= 2) {
                $daystostock = 15;
            } elseif ($AVGD_BTW_SLE <= 3) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 4) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 5) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 7) {
                $daystostock = 10;
            } elseif ($AVGD_BTW_SLE <= 10) {
                $daystostock = 8;
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
        $var_pkgu = intval($L04array[$key]['PACKAGE_UNIT']);
        $var_pkty = $L04array[$key]['PACKAGE_TYPE'];
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
    
    
    

    //loop through available L04 grids to determine smallest location to accomodate slot quantity
    foreach ($L04GridsArray as $key2 => $value) {
        $var_grid5 = $L04GridsArray[$key2]['LMGRD5'];
        $var_gridheight = $L04GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L04GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L04GridsArray[$key2]['LMWIDE'];
        $var_locvol = $L04GridsArray[$key2]['LMVOL9'];

        //Call the case true fit for L04
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];

        //if last grid in array, must assign to that grid
        if ($L04GridsArray_count == ($key2 - 1)) {
            $L04GridsArray[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($L04GridsArray[$key2]['GRIDCOUNT'] <= 0) {
                unset($L04GridsArray[$key2]);
                $L04GridsArray = array_values($L04GridsArray);  //reset array
            }
            break;
        }


        
        if ($SUGGESTED_MAX_test >= $slotqty) {
            $L04GridsArray[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($L04GridsArray[$key2]['GRIDCOUNT'] <= 0) {
                unset($L04GridsArray[$key2]);
                $L04GridsArray = array_values($L04GridsArray);  //reset array
            }
            break;
        }
    }

    


    $SUGGESTED_MAX = $SUGGESTED_MAX_test;

//Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));

//append data to array for writing to my_npfmvc table
    $L04array[$key]['SUGGESTED_TIER'] = 'L04';
    $L04array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $L04array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L04array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L04array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L04array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L04array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
    $L04array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L04array[$key]['CURMAX'], $L04array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L04array[$key]['SHIP_QTY_MN'], $L04array[$key]['AVGD_BTW_SLE']);
    $L04array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L04array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval($daystostock);

    if (count($L04GridsArray) <= 0) {
        break;
    }
}



//********** START of SQL to ADD TO TABLE **********
$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($L04array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = ($L04array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L04array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L04array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L04array[$counter]['PACKAGE_TYPE'];
        $CUR_LOCATION = $L04array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L04array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = ($L04array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($L04array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($L04array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($L04array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $L04array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($L04array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $L04array[$counter]['SHIP_QTY_SD'];
        $CPCEPKU = intval($L04array[$counter]['CPCEPKU']);
        $CPCCPKU = intval($L04array[$counter]['CPCCPKU']);
        $CPCFLOW = $L04array[$counter]['CPCFLOW'];
        if (is_null($CPCFLOW)) {
            $CPCFLOW = ' ';
        }
        $CPCTOTE = $L04array[$counter]['CPCTOTE'];
        if (is_null($CPCTOTE)) {
            $CPCTOTE = ' ';
        }
        $CPCSHLF = $L04array[$counter]['CPCSHLF'];
        if (is_null($CPCSHLF)) {
            $CPCSHLF = ' ';
        }
        $CPCROTA = $L04array[$counter]['CPCROTA'];
        if (is_null($CPCROTA)) {
            $CPCROTA = ' ';
        }
        $CPCESTK = intval($L04array[$counter]['CPCESTK']);
        if (is_null($CPCESTK)) {
            $CPCESTK = ' ';
        }
        $CPCLIQU = $L04array[$counter]['CPCLIQU'];
        if (is_null($CPCLIQU)) {
            $CPCLIQU = ' ';
        }
        $CPCELEN = $L04array[$counter]['CPCELEN'];
        $CPCEHEI = $L04array[$counter]['CPCEHEI'];
        $CPCEWID = $L04array[$counter]['CPCEWID'];
         $CPCCLEN = $L04array[$counter]['CPCCLEN'];
        if (is_null($CPCCLEN)) {
            $CPCCLEN = 0;
        }
        $CPCCHEI = $L04array[$counter]['CPCCHEI'];
        if (is_null($CPCCHEI)) {
            $CPCCHEI = 0;
        }
        $CPCCWID = $L04array[$counter]['CPCCWID'];
        if (is_null($CPCCWID)) {
            $CPCCWID = 0;
        }
        $LMHIGH = ($L04array[$counter]['LMHIGH']);
        $LMDEEP = ($L04array[$counter]['LMDEEP']);
        $LMWIDE = ($L04array[$counter]['LMWIDE']);
        $LMVOL9 = ($L04array[$counter]['LMVOL9']);
        $LMTIER = $L04array[$counter]['LMTIER'];
        $LMGRD5 = $L04array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $L04array[$counter]['DLY_CUBE_VEL'];
        $DLY_PICK_VEL = $L04array[$counter]['DLY_PICK_VEL'];
        $SUGGESTED_TIER = $L04array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $L04array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $L04array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($L04array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($L04array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($L04array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($L04array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($L04array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = ($L04array[$counter]['SUGGESTED_NEWLOCVOL']);
        $SUGGESTED_DAYSTOSTOCK = intval($L04array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $L04array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $L04array[$counter]['DAILYUNIT'];


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
