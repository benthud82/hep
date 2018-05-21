<?php

$JAX_ENDCAP = 0;
$skippedkeycount = 0;
$L06_pick_limit = .01;
include '../connection/connection_details.php';

$LSEpicksSQL = $conn1->prepare("SELECT 
                                                                    SUM(CASE
                                                                        WHEN ADBS >= 365 THEN 0
                                                                        WHEN DSLS >= 180 THEN 0
                                                                        WHEN AVG_PICK > AVG_UNITS THEN AVG_UNITS / ADBS
                                                                        WHEN ADBS = 0 AND DSLS = 0 THEN AVG_PICK
                                                                        WHEN ADBS = 0 THEN (AVG_PICK / DSLS)
                                                                        ELSE (AVG_PICK / ADBS)
                                                                    END) AS TOTPICKS
                                                                FROM
                                                                    hep.nptsld
                                                                WHERE
                                                                    PKTYPE = 'LSE'");
$LSEpicksSQL->execute();

$LSEpicksArray = $LSEpicksSQL->fetchAll(pdo::FETCH_ASSOC);
$LSE_Picks = intval($LSEpicksArray[0]['TOTPICKS']);
$Max_L06_picks = $L06_pick_limit * $LSE_Picks;  //maximum number of picks to reside in L06 based off daily pick forecast
//Pull in available L06 Grid5s by volume ascending order


$L06GridsSQL = $conn1->prepare("SELECT 
                                                                        DIMGROUP as LMGRD5,  HIGH as LMHIGH, DEEP as LMDEEP, WIDE as LMWIDE, VOLUME * 1000000 as LOCVOL, COUNT(*) as GRIDCOUNT
                                                                    FROM
                                                                        hep.bay_location
                                                                    WHERE
                                                                        TIER = 'L06'
                                                                    GROUP BY DIMGROUP ,  HIGH , DEEP , WIDE , VOLUME
                                                                    ORDER BY  VOLUME");
$L06GridsSQL->execute();
$L06GridsArray = $L06GridsSQL->fetchAll(pdo::FETCH_ASSOC);

$gridcount = count($L06GridsArray) - 1;
$maxL06vol = intval($L06GridsArray[$gridcount]['LOCVOL']);



$L06sql = $conn1->prepare("SELECT DISTINCT
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
                                                                HIGH AS LMHIGH,
                                                                DEEP AS LMDEEP,
                                                                WIDE AS LMWIDE,
                                                                VOLUME AS LMVOL9,
                                                                TIER AS LMTIER,
                                                                DIMGROUP AS LMGRD5,
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
                                                                JOIN hep.bay_location on LOCATION = D.loc_location
                                                    WHERE
                                                             $sql_dailypick <= 1
                                                            and F.ITEM_NUMBER IS NULL
                                                            and (PERC_PKGTYPE = 'LSE' or PERC_PKGTYPE is null)
                                                            and X.CPCEWID * X.CPCEHEI * X.CPCELEN < $maxL06vol
                                                            and A.PKTYPE ='LSE'
                                                                    AND F.ITEM_NUMBER IS NULL
                                                                    AND TIER IN ('L01' , 'L02', 'L04', 'L06')
                                                    ORDER BY $sql_dailypick asc");


$L06sql->execute();
$L06array = $L06sql->fetchAll(pdo::FETCH_ASSOC);

$running_L06_picks = 0; //initilize picks
foreach ($L06array as $key => $value) {
    if ($running_L06_picks > $Max_L06_picks) {
        break;  //if exceeded pre-determined max picks from L06
    }

    //Check OK in Shelf Setting
    $var_OKINSHLF = $L06array[$key]['CPCSHLF'];

    $var_AVGSHIPQTY = $L06array[$key]['SHIP_QTY_MN'];
    $AVGD_BTW_SLE = intval($L06array[$key]['AVGD_BTW_SLE']);
    if ($AVGD_BTW_SLE == 0) {
        $AVGD_BTW_SLE = 999;
    }

    $var_AVGINV = intval($L06array[$key]['AVG_INV_OH']);
    $avgdailyshipqty = number_format($var_AVGSHIPQTY / $AVGD_BTW_SLE, 8);
    if ($avgdailyshipqty == 0) {
        $avgdailyshipqty = .000000001;
    }

    $avgdailypickqty = $L06array[$key]['DAILYPICK'];
    $var_PCLIQU = $L06array[$key]['CPCLIQU'];
    $var_PCEHEIin = $L06array[$key]['CPCEHEI'];
    $var_PCELENin = $L06array[$key]['CPCELEN'];
    $var_PCEWIDin = $L06array[$key]['CPCEWID'];
    $var_eachqty = $L06array[$key]['CPCEPKU'];
    $PKGU_PERC_Restriction = $L06array[$key]['PERC_PERC'];
    $ITEM_NUMBER = intval($L06array[$key]['ITEM_NUMBER']);


    $slotqty = intval(ceil($var_AVGINV * $PKGU_PERC_Restriction)); //does it make sense to slot slow movers to average inventory?

    if (($slotqty * $var_AVGINV) == 0) {  //if both slot qty and avg inv = 0, then default to 1 unit as slot qty
        $slotqty = 1;
    }

    //calculate total slot valume to determine what grid to start
    $totalslotvol = $slotqty * $var_PCEHEIin * $var_PCELENin * $var_PCEWIDin;

    //loop through available L06 grids to determine smallest location to accomodate slot quantity
    foreach ($L06GridsArray as $key2 => $value) {
        $keycount = count($L06GridsArray) - 1;
        $var_locvol = $L06GridsArray[$key2]['LOCVOL'];
        //if total slot volume is less than location volume, then continue
        $var_grid5 = $L06GridsArray[$key2]['LMGRD5'];
        $var_gridheight = $L06GridsArray[$key2]['LMHIGH'];
        $var_griddepth = $L06GridsArray[$key2]['LMDEEP'];
        $var_gridwidth = $L06GridsArray[$key2]['LMWIDE'];



        //Call the  true fit for L06
        $SUGGESTED_MAX_array = _truefitgrid2iterations($var_grid5, $var_gridheight, $var_griddepth, $var_gridwidth, $var_PCLIQU, $var_PCEHEIin, $var_PCELENin, $var_PCEWIDin);
        $SUGGESTED_MAX_test = $SUGGESTED_MAX_array[1];



        if ($SUGGESTED_MAX_test >= $slotqty) {
            $L06GridsArray[$key2]['GRIDCOUNT'] -= 1;  //subtract used grid from array as no longer available
            if ($L06GridsArray[$key2]['GRIDCOUNT'] <= 0) {
                unset($L06GridsArray[$key2]);
                $L06GridsArray = array_values($L06GridsArray);  //reset array
            }
            break;
        }
    }

    if ($keycount == $key2 && $SUGGESTED_MAX_test < $slotqty) {  //could not find a location that could hold slot qty
        $skippedkeycount += 1;
        unset($L06array[$key]);
        continue;
    }

    $SUGGESTED_MAX = $SUGGESTED_MAX_test;
    //Call the min calc logic
    $SUGGESTED_MIN = intval(_minloc($SUGGESTED_MAX, $var_AVGSHIPQTY, $var_eachqty));
    if ($SUGGESTED_MIN == 0) {
        $SUGGESTED_MIN = 1;
    }

    //append data to array for writing to my_npfmvc table
    $L06array[$key]['SUGGESTED_TIER'] = 'L06';
    $L06array[$key]['SUGGESTED_GRID5'] = $var_grid5;
    $L06array[$key]['SUGGESTED_DEPTH'] = $var_griddepth;
    $L06array[$key]['SUGGESTED_MAX'] = $SUGGESTED_MAX;
    $L06array[$key]['SUGGESTED_MIN'] = $SUGGESTED_MIN;
    $L06array[$key]['SUGGESTED_SLOTQTY'] = $slotqty;
    $L06array[$key]['SUGGESTED_IMPMOVES'] = _implied_daily_moves($SUGGESTED_MAX, $SUGGESTED_MIN, $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
    $L06array[$key]['CURRENT_IMPMOVES'] = _implied_daily_moves($L06array[$key]['CURMAX'], $L06array[$key]['CURMIN'], $avgdailyshipqty, $var_AVGINV, $L06array[$key]['SHIP_QTY_MN'], $L06array[$key]['AVGD_BTW_SLE']);
    $L06array[$key]['SUGGESTED_NEWLOCVOL'] = $var_locvol;
    $L06array[$key]['SUGGESTED_DAYSTOSTOCK'] = intval(0);

    $running_L06_picks += $avgdailypickqty;
    if (count($L06GridsArray) <= 0) {  //no more available locations
        break;
    }
}


//L06 items have been designated.  Loop through L06 array to add to my_npfmvc 
//delete unassigned items from array using $key as the last offset
array_splice($L06array, ($key - $skippedkeycount));

$L06array = array_values($L06array);  //reset array



$values = array();
$intranid = 0;
$maxrange = 999;
$counter = 0;
$rowcount = count($L06array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 1000 lines segments to insert into table my_npfmvc
        $WAREHOUSE = ($L06array[$counter]['WAREHOUSE']);
        $ITEM_NUMBER = intval($L06array[$counter]['ITEM_NUMBER']);
        $PACKAGE_UNIT = intval($L06array[$counter]['PACKAGE_UNIT']);
        $PACKAGE_TYPE = $L06array[$counter]['PACKAGE_TYPE'];
        $CUR_LOCATION = $L06array[$counter]['LMLOC'];
        $DAYS_FRM_SLE = intval($L06array[$counter]['DAYS_FRM_SLE']);
        $AVGD_BTW_SLE = ($L06array[$counter]['AVGD_BTW_SLE']);
        $AVG_INV_OH = intval($L06array[$counter]['AVG_INV_OH']);
        $NBR_SHIP_OCC = intval($L06array[$counter]['NBR_SHIP_OCC']);
        $PICK_QTY_MN = intval($L06array[$counter]['PICK_QTY_MN']);
        $PICK_QTY_SD = $L06array[$counter]['PICK_QTY_SD'];
        $SHIP_QTY_MN = intval($L06array[$counter]['SHIP_QTY_MN']);
        $SHIP_QTY_SD = $L06array[$counter]['SHIP_QTY_SD'];
        $CPCEPKU = intval($L06array[$counter]['CPCEPKU']);
        $CPCCPKU = intval($L06array[$counter]['CPCCPKU']);
        $CPCFLOW = $L06array[$counter]['CPCFLOW'];
        if (is_null($CPCFLOW)) {
            $CPCFLOW = ' ';
        }
        $CPCTOTE = $L06array[$counter]['CPCTOTE'];
        if (is_null($CPCTOTE)) {
            $CPCTOTE = ' ';
        }
        $CPCSHLF = $L06array[$counter]['CPCSHLF'];
        if (is_null($CPCSHLF)) {
            $CPCSHLF = ' ';
        }
        $CPCROTA = $L06array[$counter]['CPCROTA'];
        if (is_null($CPCROTA)) {
            $CPCROTA = ' ';
        }
        $CPCESTK = intval($L06array[$counter]['CPCESTK']);
        if (is_null($CPCESTK)) {
            $CPCESTK = ' ';
        }
        $CPCLIQU = $L06array[$counter]['CPCLIQU'];
        if (is_null($CPCLIQU)) {
            $CPCLIQU = ' ';
        }
        $CPCELEN = $L06array[$counter]['CPCELEN'];
        $CPCEHEI = $L06array[$counter]['CPCEHEI'];
        $CPCEWID = $L06array[$counter]['CPCEWID'];
        $CPCCLEN = $L06array[$counter]['CPCCLEN'];
        if (is_null($CPCCLEN)) {
            $CPCCLEN = 0;
        }
        $CPCCHEI = $L06array[$counter]['CPCCHEI'];
        if (is_null($CPCCHEI)) {
            $CPCCHEI = 0;
        }
        $CPCCWID = $L06array[$counter]['CPCCWID'];
        if (is_null($CPCCWID)) {
            $CPCCWID = 0;
        }
        $LMHIGH = ($L06array[$counter]['LMHIGH']);
        $LMDEEP = ($L06array[$counter]['LMDEEP']);
        $LMWIDE = ($L06array[$counter]['LMWIDE']);
        $LMVOL9 = ($L06array[$counter]['LMVOL9']);
        $LMTIER = $L06array[$counter]['LMTIER'];
        $LMGRD5 = $L06array[$counter]['LMGRD5'];
        $DLY_CUBE_VEL = $L06array[$counter]['DLY_CUBE_VEL'];
        $DLY_PICK_VEL = $L06array[$counter]['DLY_PICK_VEL'];
        $SUGGESTED_TIER = $L06array[$counter]['SUGGESTED_TIER'];
        $SUGGESTED_GRID5 = $L06array[$counter]['SUGGESTED_GRID5'];
        $SUGGESTED_DEPTH = $L06array[$counter]['SUGGESTED_DEPTH'];
        $SUGGESTED_MAX = intval($L06array[$counter]['SUGGESTED_MAX']);
        $SUGGESTED_MIN = intval($L06array[$counter]['SUGGESTED_MIN']);
        $SUGGESTED_SLOTQTY = intval($L06array[$counter]['SUGGESTED_SLOTQTY']);

        $SUGGESTED_IMPMOVES = ($L06array[$counter]['SUGGESTED_IMPMOVES']);
        $CURRENT_IMPMOVES = ($L06array[$counter]['CURRENT_IMPMOVES']);
        $SUGGESTED_NEWLOCVOL = ($L06array[$counter]['SUGGESTED_NEWLOCVOL']) / 1000000;
        $SUGGESTED_DAYSTOSTOCK = intval($L06array[$counter]['SUGGESTED_DAYSTOSTOCK']);
        $AVG_DAILY_PICK = $L06array[$counter]['DAILYPICK'];
        $AVG_DAILY_UNIT = $L06array[$counter]['DAILYUNIT'];


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

