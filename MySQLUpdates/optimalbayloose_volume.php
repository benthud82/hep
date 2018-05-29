<?php

$whssel = 'HEP';
$level_array = array('A', 'B', 'C');
$OPT_LOCATION = '';
$columns = 'OPT_WHSE, OPT_ITEM, OPT_PKGU, OPT_LOC, OPT_ADBS, OPT_CSLS, OPT_CUBE, OPT_CURTIER, OPT_TOTIER, OPT_NEWGRID, OPT_NDEP, OPT_AVGPICK, OPT_DAILYPICKS, OPT_NEWGRIDVOL, OPT_PPCCALC, OPT_OPTWALKFEET, OPT_CURRWALKFEET, OPT_CURRDAILYFT, OPT_SHLDDAILYFT, OPT_ADDTLFTPERPICK, OPT_ADDTLFTPERDAY, OPT_WALKCOST, OPT_LOCATION, OPT_BUILDING, OPT_LEVEL';


ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connection/connection_details.php';
include_once '../globalfunctions/slottingfunctions.php';
include_once 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites

$OPT_BUILDING = intval(1);
$sqldelete = "TRUNCATE  hep.optimalbay";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//Assign optimal bay for L06 outside of level loop

$baycube_L06 = $conn1->prepare("SELECT 
                                                    slotmaster_dimgroup AS LMGRD5,
                                                    slotmaster_distance as WALKFEET,
                                                    COUNT(*) AS GRIDCOUNT
                                                FROM
                                                    hep.slotmaster
                                                WHERE
                                                    slotmaster_tier = 'L06'
                                                GROUP BY slotmaster_dimgroup , slotmaster_distance
                                                ORDER BY slotmaster_usecube, slotmaster_distance");
$baycube_L06->execute();
$baycubearray_L06 = $baycube_L06->fetchAll(pdo::FETCH_ASSOC);

//Result set for PPC sorted by highest PPC for items currently in L04
$ppc_L06 = $conn1->prepare("SELECT 
    A.WAREHOUSE AS OPT_WHSE,
    A.ITEM_NUMBER AS OPT_ITEM,
    A.PACKAGE_UNIT AS OPT_PKGU,
    A.CUR_LOCATION AS OPT_LOC,
    A.AVGD_BTW_SLE AS OPT_ADBS,
    A.PACKAGE_TYPE AS OPT_CSLS,
    CASE
        WHEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 THEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
        ELSE (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
    END AS OPT_CUBE,
    A.LMTIER AS OPT_CURTIER,
    A.SUGGESTED_TIER AS OPT_TOTIER,
    A.SUGGESTED_GRID5 AS OPT_NEWGRID,
    A.SUGGESTED_DEPTH AS OPT_NDEP,
    A.PICK_QTY_MN AS OPT_AVGPICK,
A.AVG_DAILY_PICK AS OPT_DAILYPICKS,
    SUGGESTED_NEWLOCVOL AS OPT_NEWGRIDVOL,
    (A.AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) * 1000 AS OPT_PPCCALC,
    slotmaster_distance AS CURWALKFEET,
    HOLDTIER,
    HOLDGRID,
    HOLDLOCATION,
    slotmaster_walkbay AS CURR_BAY
FROM
    hep.my_npfmvc A
        JOIN
    hep.slotmaster L ON slotmaster_loc = CUR_LOCATION
        JOIN
    hep.npfcpcsettings X ON X.CPCITEM = A.ITEM_NUMBER
        LEFT JOIN
    hep.item_settings S ON S.ITEM = A.ITEM_NUMBER
WHERE
    SUGGESTED_TIER = 'L06'
ORDER BY (AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) DESC , A.SUGGESTED_NEWLOCVOL ASC");
$ppc_L06->execute();
$ppcarray_L06 = $ppc_L06->fetchAll(pdo::FETCH_ASSOC);





//assign L06s to specific location
$data = array();
foreach ($ppcarray_L06 as $key => $value) {
//is there a hold location?
    $testloc = $ppcarray_L06[$key]['HOLDLOCATION'];

    $OPT_NEWGRID = $ppcarray_L06[$key]['OPT_NEWGRID'];
    $OPT_NDEP = intval($ppcarray_L06[$key]['OPT_NDEP']);

    if (!is_null($testloc) && $testloc <> '') {
        $OPT_LOCATION = $testloc;
    } else if (sizeof($baycubearray_L06 > 0)) {
        //need to verify the location size matches

        foreach ($baycubearray_L06 as $key2 => $value) {//loop through L01 non-assigned grids
            $l01grid = $baycubearray_L06[$key2]['LMGRD5'];
            if ($OPT_NEWGRID == $l01grid) {
//                $OPT_LOCATION = $baycubearray_L06[$key2]['LMLOC'];
                $OPT_LOCATION = '';
                $OPT_Shouldwalkfeet = ($baycubearray_L06[$key2]['WALKFEET']);  //Optimal walk feet per pick

                $baycubearray_L06[$key2]['GRIDCOUNT'] -= 1;
                if ($baycubearray_L06[$key2]['GRIDCOUNT'] <= 0) {
                    unset($baycubearray_L06[$key2]);
                    $baycubearray_L06 = array_values($baycubearray_L06);  //reset array
                }
                break;
            }
        }
    } else {
        $OPT_LOCATION = '';
    }


    $OPT_TOTIER = $ppcarray_L06[$key]['OPT_TOTIER'];
    $OPT_WHSE = ($ppcarray_L06[$key]['OPT_WHSE']);
    $OPT_ITEM = intval($ppcarray_L06[$key]['OPT_ITEM']);

    $OPT_PKGU = intval($ppcarray_L06[$key]['OPT_PKGU']);
    $OPT_LOC = $ppcarray_L06[$key]['OPT_LOC'];
    $OPT_ADBS = intval($ppcarray_L06[$key]['OPT_ADBS']);
    $OPT_CSLS = $ppcarray_L06[$key]['OPT_CSLS'];
    $OPT_CUBE = intval($ppcarray_L06[$key]['OPT_CUBE']);
    $OPT_CURTIER = $ppcarray_L06[$key]['OPT_CURTIER'];
    $OPT_AVGPICK = intval($ppcarray_L06[$key]['OPT_AVGPICK']);
    $OPT_DAILYPICKS = number_format($ppcarray_L06[$key]['OPT_DAILYPICKS'], 2);
    $OPT_NEWGRIDVOL = intval($ppcarray_L06[$key]['OPT_NEWGRIDVOL']);
    $OPT_PPCCALC = $ppcarray_L06[$key]['OPT_PPCCALC'];
    $currentfeetperpick = ($ppcarray_L06[$key]['CURWALKFEET']);
    $OPT_CURRBAY = intval($ppcarray_L06[$key]['CURR_BAY']);
    $OPT_OPTBAY = intval(0);

    $walkcostarray = _walkcost_feet($currentfeetperpick, $OPT_Shouldwalkfeet, $OPT_DAILYPICKS);

    $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
    $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
    $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
    $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
    $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];
    $level = 'DV';
    $data[] = "('$OPT_WHSE', $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, '$OPT_PPCCALC','$OPT_Shouldwalkfeet', '$currentfeetperpick',  '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', '$OPT_WALKCOST', '$OPT_LOCATION',$OPT_BUILDING,'$level')";
}

$valuesl06 = array();
$valuesl06 = implode(',', $data);
if (!empty($valuesl06)) {
    $sql = "INSERT IGNORE INTO hep.optimalbay ($columns) VALUES $valuesl06";
    $query = $conn1->prepare($sql);
    $query->execute();
}





//Start of level loop
foreach ($level_array as $level) {


    $baycube_L04 = $conn1->prepare("SELECT 
                                                                            slotmaster_distance as WALKFEET,
                                                                            SUM(slotmaster_usecube) * 1000000 AS TIER_VOL
                                                                        FROM
                                                                            hep.slotmaster
                                                                        WHERE
                                                                            slotmaster_branch = 'HEP' AND slotmaster_level = '$level' and slotmaster_tier = 'L04'
                                                                        GROUP BY slotmaster_distance");
    $baycube_L04->execute();
    $baycubearray_L04 = $baycube_L04->fetchAll(pdo::FETCH_ASSOC);

    $baycube_L02 = $conn1->prepare("SELECT 
                                                    slotmaster_dimgroup AS LMGRD5,
                                                    slotmaster_distance as WALKFEET,
                                                    slotmaster_loc as LOCATION,
                                                    COUNT(*) AS GRIDCOUNT
                                                FROM
                                                    hep.slotmaster
                                                WHERE
                                                    slotmaster_level = '$level' AND slotmaster_tier = 'L02'
                                                GROUP BY slotmaster_dimgroup , slotmaster_distance, slotmaster_loc
                                                ORDER BY slotmaster_usecube, slotmaster_distance, slotmaster_loc");
    $baycube_L02->execute();
    $baycubearray_L02 = $baycube_L02->fetchAll(pdo::FETCH_ASSOC);

    $baycube_L01 = $conn1->prepare("SELECT 
                                                    slotmaster_dimgroup AS LMGRD5,
                                                    slotmaster_distance as WALKFEET,
                                                    slotmaster_loc as LOCATION,
                                                    COUNT(*) AS GRIDCOUNT
                                                FROM
                                                    hep.slotmaster
                                                WHERE
                                                    slotmaster_level = '$level' AND slotmaster_tier = 'L01'
                                                GROUP BY slotmaster_dimgroup , slotmaster_distance, slotmaster_loc
                                                ORDER BY slotmaster_usecube, slotmaster_distance, slotmaster_loc");
    $baycube_L01->execute();
    $baycubearray_L01 = $baycube_L01->fetchAll(pdo::FETCH_ASSOC);

////subtract cube from items on hold from L04 cube
//$holdcube = $conn1->prepare("SELECT 
//                                    substring(HOLDLOCATION, 4, 2) as HOLDBAY, sum(LMVOL9) as HOLDBAYVOL
//                                FROM
//                                    hep.item_settings
//                                 JOIN hep.slotmaster on LMWHSE = WHSE and LMLOC = HOLDLOCATION
//                                WHERE
//                                    WHSE = $whssel and HOLDTIER = 'L04'
//                                GROUP BY substring(HOLDLOCATION, 4, 2)");
//$holdcube->execute();
//$holdcubearray = $holdcube->fetchAll(pdo::FETCH_ASSOC);
//foreach ($holdcubearray as $key => $value) {
//    $bay = $holdcubearray[$key]['HOLDBAY'];
//    $baysubtractkey = array_search($bay, array_column($baycubearray, 'BAY'));
//    $baycubearray[$baysubtractkey]['BAYVOL'] = $baycubearray[$baysubtractkey]['BAYVOL'] - $holdcubearray[$key]['HOLDBAYVOL'];
//}
//Result set for PPC sorted by highest PPC for items currently in L04
    $ppc_L04 = $conn1->prepare("SELECT 
    A.WAREHOUSE AS OPT_WHSE,
    A.ITEM_NUMBER AS OPT_ITEM,
    A.PACKAGE_UNIT AS OPT_PKGU,
    A.CUR_LOCATION AS OPT_LOC,
    A.AVGD_BTW_SLE AS OPT_ADBS,
    A.PACKAGE_TYPE AS OPT_CSLS,
    CASE
        WHEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 THEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
        ELSE (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
    END AS OPT_CUBE,
    A.LMTIER AS OPT_CURTIER,
    A.SUGGESTED_TIER AS OPT_TOTIER,
    A.SUGGESTED_GRID5 AS OPT_NEWGRID,
    A.SUGGESTED_DEPTH AS OPT_NDEP,
    A.PICK_QTY_MN AS OPT_AVGPICK,
    A.AVG_DAILY_PICK as OPT_DAILYPICKS,
    SUGGESTED_NEWLOCVOL * 1000000 AS OPT_NEWGRIDVOL,
    (AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) * 1000 AS OPT_PPCCALC,
    slotmaster_distance AS CURWALKFEET,
    HOLDTIER,
    HOLDGRID,
    HOLDLOCATION,
    slotmaster_walkbay AS CURR_BAY
FROM
    hep.my_npfmvc A
        JOIN
    hep.slotmaster L ON slotmaster_loc = CUR_LOCATION
        JOIN
    hep.npfcpcsettings X ON X.CPCITEM = A.ITEM_NUMBER
        LEFT JOIN
    hep.item_settings S ON S.ITEM = A.ITEM_NUMBER
WHERE
    SUGGESTED_TIER = 'L04' and slotmaster_level = '$level'
ORDER BY (AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) DESC , A.SUGGESTED_NEWLOCVOL ASC");
    $ppc_L04->execute();
    $ppcarray_L04 = $ppc_L04->fetchAll(pdo::FETCH_ASSOC);


//Result set for PPC sorted by highest PPC for items currently in L01
    $ppcL01 = $conn1->prepare("SELECT 
    A.WAREHOUSE AS OPT_WHSE,
    A.ITEM_NUMBER AS OPT_ITEM,
    A.PACKAGE_UNIT AS OPT_PKGU,
    A.CUR_LOCATION AS OPT_LOC,
    A.AVGD_BTW_SLE AS OPT_ADBS,
    A.PACKAGE_TYPE AS OPT_CSLS,
    CASE
        WHEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 THEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
        ELSE (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
    END AS OPT_CUBE,
    A.LMTIER AS OPT_CURTIER,
    A.SUGGESTED_TIER AS OPT_TOTIER,
    A.SUGGESTED_GRID5 AS OPT_NEWGRID,
    A.SUGGESTED_DEPTH AS OPT_NDEP,
    A.PICK_QTY_MN AS OPT_AVGPICK,
    A.AVG_DAILY_PICK AS OPT_DAILYPICKS,
    SUGGESTED_NEWLOCVOL AS OPT_NEWGRIDVOL,
    (A.AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) * 1000 AS OPT_PPCCALC,
    slotmaster_distance AS CURWALKFEET,
    HOLDTIER,
    HOLDGRID,
    HOLDLOCATION,
    slotmaster_walkbay AS CURR_BAY
FROM
    hep.my_npfmvc A
        JOIN
    hep.slotmaster L ON slotmaster_loc = CUR_LOCATION
        JOIN
    hep.npfcpcsettings X ON X.CPCITEM = A.ITEM_NUMBER
        LEFT JOIN
    hep.item_settings S ON S.ITEM = A.ITEM_NUMBER
WHERE
    SUGGESTED_TIER = 'L01' and slotmaster_level = '$level'
ORDER BY (AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) DESC , A.SUGGESTED_NEWLOCVOL ASC");
    $ppcL01->execute();
    $ppcarray_L01 = $ppcL01->fetchAll(pdo::FETCH_ASSOC);


//Result set for PPC sorted by highest PPC for items currently in L01
    $ppcL02 = $conn1->prepare("SELECT 
    A.WAREHOUSE AS OPT_WHSE,
    A.ITEM_NUMBER AS OPT_ITEM,
    A.PACKAGE_UNIT AS OPT_PKGU,
    A.CUR_LOCATION AS OPT_LOC,
    A.AVGD_BTW_SLE AS OPT_ADBS,
    A.PACKAGE_TYPE AS OPT_CSLS,
    CASE
        WHEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID) > 0 THEN (X.CPCELEN * X.CPCEHEI * X.CPCEWID)
        ELSE (X.CPCCLEN * X.CPCCHEI * X.CPCCWID)
    END AS OPT_CUBE,
    A.LMTIER AS OPT_CURTIER,
    A.SUGGESTED_TIER AS OPT_TOTIER,
    A.SUGGESTED_GRID5 AS OPT_NEWGRID,
    A.SUGGESTED_DEPTH AS OPT_NDEP,
    A.PICK_QTY_MN AS OPT_AVGPICK,
    AVG_DAILY_PICK AS OPT_DAILYPICKS,
    SUGGESTED_NEWLOCVOL AS OPT_NEWGRIDVOL,
    (AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) * 1000 AS OPT_PPCCALC,
    slotmaster_distance AS CURWALKFEET,
    HOLDTIER,
    HOLDGRID,
    HOLDLOCATION,
    slotmaster_walkbay AS CURR_BAY
FROM
    hep.my_npfmvc A
        JOIN
    hep.slotmaster L ON slotmaster_loc = CUR_LOCATION
        JOIN
    hep.npfcpcsettings X ON X.CPCITEM = A.ITEM_NUMBER
        LEFT JOIN
    hep.item_settings S ON S.ITEM = A.ITEM_NUMBER
WHERE
    SUGGESTED_TIER = 'L02' and slotmaster_level = '$level'
ORDER BY (AVG_DAILY_PICK) / (SUGGESTED_NEWLOCVOL) DESC , A.SUGGESTED_NEWLOCVOL ASC");
    $ppcL02->execute();
    $ppcarray_L02 = $ppcL02->fetchAll(pdo::FETCH_ASSOC);


//assign L01s to specific location
    foreach ($ppcarray_L01 as $key => $value) {
//is there a hold location?
        $testloc = $ppcarray_L01[$key]['HOLDLOCATION'];

        $OPT_NEWGRID = $ppcarray_L01[$key]['OPT_NEWGRID'];
        $OPT_NDEP = intval($ppcarray_L01[$key]['OPT_NDEP']);



        if (!is_null($testloc) && $testloc <> '') {
            $OPT_LOCATION = $testloc;
        } else if (count($baycubearray_L01 > 0)) {
            //need to verify the location size matches

            foreach ($baycubearray_L01 as $key2 => $value) {//loop through L01 non-assigned grids
                $l01grid = $baycubearray_L01[$key2]['LMGRD5'];
                if ($OPT_NEWGRID == $l01grid) {
                    $OPT_LOCATION = $baycubearray_L01[$key2]['LOCATION'];
//                    $OPT_LOCATION = '';
                    $OPT_Shouldwalkfeet = ($baycubearray_L01[$key2]['WALKFEET']);  //Optimal walk feet per pick

                    $baycubearray_L01[$key2]['GRIDCOUNT'] -= 1;
                    if ($baycubearray_L01[$key2]['GRIDCOUNT'] <= 0) {
                        unset($baycubearray_L01[$key2]);
                        $baycubearray_L01 = array_values($baycubearray_L01);  //reset array
                    }
                    break;
                }
            }
        } else {
            $OPT_LOCATION = '';
        }


        $OPT_TOTIER = $ppcarray_L01[$key]['OPT_TOTIER'];
        $OPT_WHSE = ($ppcarray_L01[$key]['OPT_WHSE']);
        $OPT_ITEM = intval($ppcarray_L01[$key]['OPT_ITEM']);

        $OPT_PKGU = intval($ppcarray_L01[$key]['OPT_PKGU']);
        $OPT_LOC = $ppcarray_L01[$key]['OPT_LOC'];
        $OPT_ADBS = intval($ppcarray_L01[$key]['OPT_ADBS']);
        $OPT_CSLS = $ppcarray_L01[$key]['OPT_CSLS'];
        $OPT_CUBE = intval($ppcarray_L01[$key]['OPT_CUBE']);
        $OPT_CURTIER = $ppcarray_L01[$key]['OPT_CURTIER'];
        $OPT_AVGPICK = intval($ppcarray_L01[$key]['OPT_AVGPICK']);
        $OPT_DAILYPICKS = number_format($ppcarray_L01[$key]['OPT_DAILYPICKS'], 2);
        $OPT_NEWGRIDVOL = intval($ppcarray_L01[$key]['OPT_NEWGRIDVOL']);
        $OPT_PPCCALC = $ppcarray_L01[$key]['OPT_PPCCALC'];
        $currentfeetperpick = ($ppcarray_L01[$key]['CURWALKFEET']);
//        $OPT_CURRBAY = intval($ppcarray_L01[$key]['CURR_BAY']);
//        $OPT_OPTBAY = intval(0);

        $walkcostarray = _walkcost_feet($currentfeetperpick, $OPT_Shouldwalkfeet, $OPT_DAILYPICKS);

        $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
        $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
        $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
        $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
        $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];


        $data[] = "('$OPT_WHSE', $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, '$OPT_PPCCALC', '$OPT_Shouldwalkfeet', '$currentfeetperpick', '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', '$OPT_WALKCOST', '$OPT_LOCATION',$OPT_BUILDING,'$level')";
    }

    $valuesl01 = array();
    $valuesl01 = implode(',', $data);
    if (!empty($valuesl01)) {
        $sql = "INSERT IGNORE INTO hep.optimalbay ($columns) VALUES $valuesl01";
        $query = $conn1->prepare($sql);
        $query->execute();
    }


//assign L02s to specific location
    $data = array();
    foreach ($ppcarray_L02 as $key => $value) {
//is there a hold location?
        $testloc = $ppcarray_L02[$key]['HOLDLOCATION'];

        $OPT_NEWGRID = $ppcarray_L02[$key]['OPT_NEWGRID'];
        $OPT_NDEP = intval($ppcarray_L02[$key]['OPT_NDEP']);



        if (!is_null($testloc) && $testloc <> '') {
            $OPT_LOCATION = $testloc;
        } else if (count($baycubearray_L02 > 0)) {
            //need to verify the location size matches

            foreach ($baycubearray_L02 as $key2 => $value) {//loop through L01 non-assigned grids
                $l01grid = $baycubearray_L02[$key2]['LMGRD5'];
                if ($OPT_NEWGRID == $l01grid) {
                    $OPT_LOCATION = $baycubearray_L02[$key2]['LOCATION'];
//                    $OPT_LOCATION = '';
                    $OPT_Shouldwalkfeet = ($baycubearray_L02[$key2]['WALKFEET']);  //Optimal walk feet per pick

                    $baycubearray_L02[$key2]['GRIDCOUNT'] -= 1;
                    if ($baycubearray_L02[$key2]['GRIDCOUNT'] <= 0) {
                        unset($baycubearray_L02[$key2]);
                        $baycubearray_L02 = array_values($baycubearray_L02);  //reset array
                    }
                    break;
                }
            }
        } else {
            $OPT_LOCATION = '';
        }


        $OPT_TOTIER = $ppcarray_L02[$key]['OPT_TOTIER'];
        $OPT_WHSE = ($ppcarray_L02[$key]['OPT_WHSE']);
        $OPT_ITEM = intval($ppcarray_L02[$key]['OPT_ITEM']);

        $OPT_PKGU = intval($ppcarray_L02[$key]['OPT_PKGU']);
        $OPT_LOC = $ppcarray_L02[$key]['OPT_LOC'];
        $OPT_ADBS = intval($ppcarray_L02[$key]['OPT_ADBS']);
        $OPT_CSLS = $ppcarray_L02[$key]['OPT_CSLS'];
        $OPT_CUBE = intval($ppcarray_L02[$key]['OPT_CUBE']);
        $OPT_CURTIER = $ppcarray_L02[$key]['OPT_CURTIER'];
        $OPT_AVGPICK = intval($ppcarray_L02[$key]['OPT_AVGPICK']);
        $OPT_DAILYPICKS = number_format($ppcarray_L02[$key]['OPT_DAILYPICKS'], 2);
        $OPT_NEWGRIDVOL = intval($ppcarray_L02[$key]['OPT_NEWGRIDVOL']);
        $OPT_PPCCALC = $ppcarray_L02[$key]['OPT_PPCCALC'];
        $currentfeetperpick = ($ppcarray_L02[$key]['CURWALKFEET']);
        $OPT_CURRBAY = intval($ppcarray_L02[$key]['CURR_BAY']);
        $OPT_OPTBAY = intval(0);

        $walkcostarray = _walkcost_feet($currentfeetperpick, $OPT_Shouldwalkfeet, $OPT_DAILYPICKS);

        $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
        $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
        $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
        $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
        $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];

        $data[] = "('$OPT_WHSE', $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, '$OPT_PPCCALC','$OPT_Shouldwalkfeet', '$currentfeetperpick',  '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', '$OPT_WALKCOST', '$OPT_LOCATION',$OPT_BUILDING,'$level')";
    }

    $valuesl02 = array();
    $valuesl02 = implode(',', $data);
    if (!empty($valuesl02)) {
        $sql = "INSERT IGNORE INTO hep.optimalbay ($columns) VALUES $valuesl02";
        $query = $conn1->prepare($sql);
        $query->execute();
    }


//assign L04s to specific location
    $data = array();
    foreach ($ppcarray_L04 as $key => $value) {
//is there a hold location?
        $testloc = $ppcarray_L04[$key]['HOLDLOCATION'];

        $OPT_NEWGRID = $ppcarray_L04[$key]['OPT_NEWGRID'];
        $OPT_NDEP = intval($ppcarray_L04[$key]['OPT_NDEP']);
        if (!is_null($testloc) && $testloc <> '') {
            $OPT_LOCATION = $testloc;
        } else {
            $OPT_LOCATION = '';
        }
        $OPT_NEWGRIDVOL = intval($ppcarray_L04[$key]['OPT_NEWGRIDVOL']);

        $OPT_Shouldwalkfeet = ($baycubearray_L04[0]['WALKFEET']);  //Optimal walk feet per pick
        //subtract volume from $baycubearray_L04
        $baycubearray_L04[0]['TIER_VOL'] -= $OPT_NEWGRIDVOL;
        if ($baycubearray_L04[0]['TIER_VOL'] <= 0) {
            unset($baycubearray_L04[0]);
            $baycubearray_L04 = array_values($baycubearray_L04);  //reset array
        }



//        foreach ($baycubearray_L04 as $key2 => $value) {//loop through L01 non-assigned grids
//            $l01grid = $baycubearray_L04[$key2]['LMGRD5'];
//            if ($OPT_NEWGRID == $l01grid) {
////                $OPT_LOCATION = $baycubearray_L04[$key2]['LMLOC'];
//                $OPT_LOCATION = '';
//                $OPT_Shouldwalkfeet = intval($baycubearray_L04[$key2]['WALKFEET']);  //Optimal walk feet per pick
//
//                $baycubearray_L04[$key2]['GRIDCOUNT'] -= 1;
//                if ($baycubearray_L04[$key2]['GRIDCOUNT'] <= 0) {
//                    unset($baycubearray_L04[$key2]);
//                    $baycubearray_L04 = array_values($baycubearray_L04);  //reset array
//                }
//                break;
//            }
//        }



        $OPT_TOTIER = $ppcarray_L04[$key]['OPT_TOTIER'];
        $OPT_WHSE = ($ppcarray_L04[$key]['OPT_WHSE']);
        $OPT_ITEM = intval($ppcarray_L04[$key]['OPT_ITEM']);

        $OPT_PKGU = intval($ppcarray_L04[$key]['OPT_PKGU']);
        $OPT_LOC = $ppcarray_L04[$key]['OPT_LOC'];
        $OPT_ADBS = intval($ppcarray_L04[$key]['OPT_ADBS']);
        $OPT_CSLS = $ppcarray_L04[$key]['OPT_CSLS'];
        $OPT_CUBE = intval($ppcarray_L04[$key]['OPT_CUBE']);
        $OPT_CURTIER = $ppcarray_L04[$key]['OPT_CURTIER'];
        $OPT_AVGPICK = intval($ppcarray_L04[$key]['OPT_AVGPICK']);
        $OPT_DAILYPICKS = number_format($ppcarray_L04[$key]['OPT_DAILYPICKS'], 2);

        $OPT_PPCCALC = $ppcarray_L04[$key]['OPT_PPCCALC'];
        $currentfeetperpick = ($ppcarray_L04[$key]['CURWALKFEET']);
        $OPT_CURRBAY = intval($ppcarray_L04[$key]['CURR_BAY']);
        $OPT_OPTBAY = intval(0);

        $walkcostarray = _walkcost_feet($currentfeetperpick, $OPT_Shouldwalkfeet, $OPT_DAILYPICKS);

        $OPT_CURRDAILYFT = ($walkcostarray['CURR_FT_PER_DAY']);
        $OPT_SHLDDAILYFT = ($walkcostarray['SHOULD_FT_PER_DAY']);
        $OPT_ADDTLFTPERPICK = ($walkcostarray['ADDTL_FT_PER_PICK']);
        $OPT_ADDTLFTPERDAY = ($walkcostarray['ADDTL_FT_PER_DAY']);
        $OPT_WALKCOST = $walkcostarray['ADDTL_COST_PER_YEAR'];

        $data[] = "('$OPT_WHSE', $OPT_ITEM, $OPT_PKGU, '$OPT_LOC', $OPT_ADBS, '$OPT_CSLS', $OPT_CUBE, '$OPT_CURTIER', '$OPT_TOTIER', '$OPT_NEWGRID', $OPT_NDEP, $OPT_AVGPICK, '$OPT_DAILYPICKS', $OPT_NEWGRIDVOL, '$OPT_PPCCALC', '$OPT_Shouldwalkfeet', '$currentfeetperpick',  '$OPT_CURRDAILYFT', '$OPT_SHLDDAILYFT', '$OPT_ADDTLFTPERPICK', '$OPT_ADDTLFTPERDAY', '$OPT_WALKCOST', '$OPT_LOCATION',$OPT_BUILDING,'$level')";
    }

    $valuesl04 = array();
    $valuesl04 = implode(',', $data);
    if (!empty($valuesl04)) {
        $sql = "INSERT IGNORE INTO hep.optimalbay ($columns) VALUES $valuesl04";
        $query = $conn1->prepare($sql);
        $query->execute();
    }

//end of level array   
}




//update history table

$sql_hist = "INSERT IGNORE INTO hep.optimalbay_hist(optbayhist_whse, optbayhist_tier, optbayhist_date, optbayhist_bay, optbayhist_pick, optbayhist_cost, optbayhist_count)
        SELECT 
    'HEP',
    L.slotmaster_tier,
    CURDATE(),
    L.slotmaster_bay AS BAY,
    CASE
        WHEN SUM(AVG_DAILY_PICK) IS NULL THEN 0
        ELSE SUM(AVG_DAILY_PICK)
    END AS PICKQTY,
    AVG(ABS(1)),
    COUNT(*)
FROM
    hep.slotmaster L
        LEFT JOIN
    hep.item_location ON loc_location = slotmaster_loc
        LEFT JOIN
    hep.nptsld ON ITEM = loc_item
GROUP BY 'HEP' , L.slotmaster_tier , CURDATE() , L.slotmaster_bay";
$query_hist = $conn1->prepare($sql_hist);
$query_hist->execute();
