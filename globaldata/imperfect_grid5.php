
<?php

include_once '../connection/connection_details.php';
$displayarray[$topcostkey]['MOVES_AFTER_IMP_GRID'] = '-';
$displayarray[$topcostkey]['MOVESCORE_AFTER_IMP_GRID'] = '-';
$displayarray[$topcostkey]['WALKSCORE_AFTER_IMP_GRID'] = '-';
$displayarray[$topcostkey]['TOTSCORE_AFTER_IMP_GRID'] = '-';
$tier = $TOP_REPLEN_COST_array[$topcostkey]['SUGGESTED_TIER'];

include 'emptygridsdata.php';


foreach ($EMPTYGRID_array as $key3 => $value3) {


    $testgrid5 = $EMPTYGRID_array[$key3]['LMGRD5'];  //largest empty grid available
    //Determine if each or case truefit should be used.
    $caseoreachTF = _caseoreachtf($EMPTYGRID_array[$key3]['LMTIER']); //call function to determine which TF function to access.  Using the tier of the empty grid to determine which tf (case or each) to use
    if ($PCCHEI * $PCCLEN * $PCCWID * $PCCPKU > 0) { //if there is a case pkgu and all dimensions are set
        if ($caseoreachTF == 'Case') {
            //use case tf function
            $NEW_LOC_TRUEFIT_array = _truefitgrid2iterations_case($testgrid5, $EMPTYGRID_array[$key3]['LMHIGH'], $EMPTYGRID_array[$key3]['LMDEEP'], $EMPTYGRID_array[$key3]['LMWIDE'], $PCLIQU, $PCCHEI, $PCCLEN, $PCCWID, $PCCPKU);  //call funcation to calculate TF based of grid5
        } elseif ($PCEHEI * $PCELEN * $PCEWID * $PCEPKU > 0) {
            //use each tf function
            $NEW_LOC_TRUEFIT_array = _truefitgrid2iterations($testgrid5, $EMPTYGRID_array[$key3]['LMHIGH'], $EMPTYGRID_array[$key3]['LMDEEP'], $EMPTYGRID_array[$key3]['LMWIDE'], $PCLIQU, $PCEHEI, $PCELEN, $PCEWID);  //call funcation to calculate TF based of grid5
        }
    } elseif ($PCEHEI * $PCELEN * $PCEWID * $PCEPKU > 0) {
        $NEW_LOC_TRUEFIT_array = _truefitgrid2iterations($testgrid5, $EMPTYGRID_array[$key3]['LMHIGH'], $EMPTYGRID_array[$key3]['LMDEEP'], $EMPTYGRID_array[$key3]['LMWIDE'], $PCLIQU, $PCEHEI, $PCELEN, $PCEWID);  //call funcation to calculate TF based of grid5
    } else {
        continue;
    }
    if (count($NEW_LOC_TRUEFIT_array) > 0) {
        $NEW_LOC_TRUEFIT_round2 = $NEW_LOC_TRUEFIT_array[1]; //assign 2-iteration tf to variable
//        $tf_to_newdmdcalc = number_format($NEW_LOC_TRUEFIT_round2 / $TOP_REPLEN_COST_array[$topcostkey]['SUGGESTED_SLOTQTY'], 2);  //compare calculated TF to desired slotting quantity to determine if need to contiue to next grid5
//        $tf_to_curmaxcalc = number_format($NEW_LOC_TRUEFIT_round2 / $TOP_REPLEN_COST_array[$topcostkey]['CURMAX'], 2);
    }

    $lookupkey_l3 = $EMPTYGRID_array[$key3]['EMPTYGRID'];  //grid5 keyval to lookup in empty locations
    $IMPERFECT_GRID5_key = array_search($lookupkey_l3, array_column($EMPTYLOC_array, 'KEYVAL'));
    if ($IMPERFECT_GRID5_key <> FALSE) {

        $NEW_LOC = $EMPTYLOC_array[$IMPERFECT_GRID5_key]['slotmaster_loc'];
        $displayarray[$topcostkey]['IMPERFECT_GRID5_SLOT_LOC'] = $NEW_LOC;
        $NEW_GRD5 = $EMPTYLOC_array[$IMPERFECT_GRID5_key]['slotmaster_dimgroup'];
        $displayarray[$topcostkey]['AssgnGrid5'] = $NEW_GRD5; //Add new grid5 to display array

        $Newmin = _minloc($NEW_LOC_TRUEFIT_round2, $TOP_REPLEN_COST_array[$topcostkey]['SHIP_QTY_MN'], $TOP_REPLEN_COST_array[$topcostkey]['CPCCPKU']);
        $impmoves_after_perfloc = _implied_daily_moves($NEW_LOC_TRUEFIT_round2, $Newmin, $TOP_REPLEN_COST_array[$topcostkey]['AVG_DAILY_UNIT'], $TOP_REPLEN_COST_array[$topcostkey]['AVG_INV_OH'], $TOP_REPLEN_COST_array[$topcostkey]['SHIP_QTY_MN'], $TOP_REPLEN_COST_array[$topcostkey]['AVGD_BTW_SLE']);
        $replen_score_Perf_Loc = _replen_score_from_moves($impmoves_after_perfloc);

        if ($zone == 'CSE') { //calculate LSE or CSE walk cost
            $walk_score_Perf_Loc_array = _walkcost_case($VCFTIR, $VCTTIR, $TOP_REPLEN_COST_array[$topcostkey]['AVG_DAILY_UNIT'], $TOP_REPLEN_COST_array[$topcostkey]['FLOOR']);
            $walk_score_Perf_Loc = 1;
        } else {
            $walk_score_Perf_Loc = _walkscore( $EMPTYGRID_array[$key3]['slotmaster_distance'], $OPT_OPTBAY, $TOP_REPLEN_COST_array[$topcostkey]['AVG_DAILY_PICK']);
        }



        if (($walk_score_Perf_Loc * $replen_score_Perf_Loc) > ($TOP_REPLEN_COST_array[$topcostkey]['SCORE_TOTALSCORE'] * 1.25)) {  //if there is a 25% increase in total score then continue
            $displayarray[$topcostkey]['MOVES_AFTER_IMP_GRID'] = $impmoves_after_perfloc;
            $displayarray[$topcostkey]['MOVESCORE_AFTER_IMP_GRID'] = $replen_score_Perf_Loc;
            $displayarray[$topcostkey]['WALKSCORE_AFTER_IMP_GRID'] = $walk_score_Perf_Loc;
            $displayarray[$topcostkey]['TOTSCORE_AFTER_IMP_GRID'] = abs($replen_score_Perf_Loc) * abs($walk_score_Perf_Loc);
            unset($EMPTYLOC_array[$IMPERFECT_GRID5_key]);
            $EMPTYLOC_array = array_values($EMPTYLOC_array);
            break; //break out of foreach loop once match is made
        } else {
            $displayarray[$topcostkey]['MOVES_AFTER_IMP_GRID'] = '-';
            $displayarray[$topcostkey]['MOVESCORE_AFTER_IMP_GRID'] = '-';
            $displayarray[$topcostkey]['WALKSCORE_AFTER_IMP_GRID'] = '-';
            $displayarray[$topcostkey]['TOTSCORE_AFTER_IMP_GRID'] = '-';
        }
    }
}

if ($IMPERFECT_GRID5_key === FALSE) {
    $displayarray[$topcostkey]['MOVES_AFTER_IMP_GRID'] = '-';
    $displayarray[$topcostkey]['MOVESCORE_AFTER_IMP_GRID'] = '-';
    $displayarray[$topcostkey]['WALKSCORE_AFTER_IMP_GRID'] = '-';
    $displayarray[$topcostkey]['TOTSCORE_AFTER_IMP_GRID'] = '-';
}
 