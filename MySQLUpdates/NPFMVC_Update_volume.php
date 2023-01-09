<?php

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
//main core file to update slotting recommendation file --MY_NPFMVC--
//global includes

include_once '../globalfunctions/slottingfunctions.php';
include_once '../globalfunctions/newitem.php';

include_once 'sql_dailypick.php';  //pulls in variable $sql_dailypick to calculate daily pick quantites
//assign columns variable for my_npfmvc table
$columns = 'WAREHOUSE, ITEM_NUMBER, PACKAGE_UNIT, PACKAGE_TYPE, CUR_LOCATION, CUR_LEVEL, DAYS_FRM_SLE, AVGD_BTW_SLE, AVG_INV_OH, NBR_SHIP_OCC, PICK_QTY_MN, PICK_QTY_SD, SHIP_QTY_MN, SHIP_QTY_SD,CPCEPKU,CPCCPKU,CPCFLOW,CPCTOTE,CPCSHLF,CPCROTA,CPCESTK,CPCLIQU,CPCELEN,CPCEHEI,CPCEWID,CPCCLEN,CPCCHEI,CPCCWID,LMHIGH,LMDEEP,LMWIDE,LMVOL9,LMTIER,LMGRD5,DLY_CUBE_VEL,DLY_PICK_VEL,SUGGESTED_TIER,SUGGESTED_GRID5,SUGGESTED_DEPTH,SUGGESTED_MAX,SUGGESTED_MIN,SUGGESTED_SLOTQTY,SUGGESTED_IMPMOVES,CURRENT_IMPMOVES,SUGGESTED_NEWLOCVOL,SUGGESTED_DAYSTOSTOCK, AVG_DAILY_PICK, AVG_DAILY_UNIT,  JAX_ENDCAP';

include_once '../connection/connection_details.php';
//$whsearray = array(2, 3, 6, 7, 9, 11, 12, 16);
//Delete inventory restricted items
$sqldelete3 = "TRUNCATE hep.inventory_restricted ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

$sqldelete = "TRUNCATE hep.my_npfmvc ";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$holdvolumearray = array();
//Logic for L06 update.  Must do outside of levels loop
include 'L06update_bycurrentgrids.php';

$whssel = 'HEP';
$levels_array = array('A', 'B', 'C');

foreach ($levels_array as $level) {

//--pull in available tiers--
    $alltiersql = $conn1->prepare("SELECT 
                                                                        slotmaster_branch AS TIER_WHS,
                                                                        slotmaster_tier AS TIER_TIER,
                                                                        COUNT(*) AS TIER_COUNT
                                                                    FROM
                                                                        hep.slotmaster
                                                                    WHERE
                                                                        slotmaster_branch = '$whssel'
                                                                            AND slotmaster_level = '$level'
                                                                    GROUP BY slotmaster_branch , slotmaster_tier; ");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $alltiersql->execute();
    $alltierarray = $alltiersql->fetchAll(pdo::FETCH_ASSOC);

//--pull in volume by tier--
    $allvolumesql = $conn1->prepare("SELECT 
                                                                            slotmaster_branch AS LMWHSE,
                                                                            slotmaster_tier AS LMTIER,
                                                                            SUM(slotmaster_usecube) * 1000000 AS TIER_VOL
                                                                        FROM
                                                                            hep.slotmaster
                                                                        WHERE
                                                                            slotmaster_branch = '$whssel' and 
                                                                                slotmaster_level = '$level'
                                                                        GROUP BY slotmaster_branch , slotmaster_tier; ");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $allvolumesql->execute();
    $allvolumearray = $allvolumesql->fetchAll(pdo::FETCH_ASSOC);

//Assign items on hold
//    include_once 'itemsonhold.php';
    $L01onholdcount = 0;

//call L01 Update logic
    $L01key = array_search('L01', array_column($alltierarray, 'TIER_TIER')); //Find 'L01' associated key
    $L01onholdkey = array_search('L01', array_column($holdvolumearray, 'SUGGESTED_TIER'));
    if ($L01onholdkey !== FALSE) {
        $L01onholdcount = intval($holdvolumearray[$L01onholdkey]['ASSCOUNT']);
    }
    if ($L01key !== FALSE) {
        include 'L01update.php';
    }

//call L02 Update logic
    $L02key = array_search('L02', array_column($alltierarray, 'TIER_TIER')); //Find 'L01' associated key
    if ($L02key !== FALSE) {
        include 'L02update_bycurrentgrids.php';
    }

//Standard Blue bin update
    include 'L04update_byvolume.php';
}//end of $level loop
//Slot any item that currently has a location but no sales data.  Do outside of levels loop
//include 'nosalesupdate.php';
