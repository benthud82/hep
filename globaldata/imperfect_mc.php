
<?php

include_once '../connection/connection_details.php';
$displayarray[$topcostkey]['AFTER_IMPERFECT_MC_SLOT_MOVES'] = '-';
$displayarray[$topcostkey]['MOVESCORE_AFTER_IMPERFECT_MC'] = '-';
$displayarray[$topcostkey]['WALKSCORE_AFTER_IMPERFECT_MC'] = '-';
$displayarray[$topcostkey]['TOTSCORE_AFTER_IMPERFECT_MC'] = '-';



include_once '../globalfunctions/slottingfunctions.php';
$IMPERF_MC_TIER = $TOP_REPLEN_COST_array[$topcostkey]['SUGGESTED_TIER'];
$IMPERF_MC_GRID5 = $TOP_REPLEN_COST_array[$topcostkey]['SUGGESTED_GRID5'];
$IMPERF_MC_DEEP = $TOP_REPLEN_COST_array[$topcostkey]['SUGGESTED_DEPTH'];


$acceptablebays = _AcceptBayFunction($OPT_OPTBAY); //No longer MC but based of Jeromy's bay structure


foreach ($acceptablebays as $value_l2) {

    $IMPERFMC = $CUR_LEVEL. $IMPERF_MC_TIER . $IMPERF_MC_GRID5 . $IMPERF_MC_DEEP . $value_l2;
    $IMPERFECT_MC_key = array_search($IMPERFMC, array_column($EMPTYLOC_array, 'KEYVAL'));

    if ($IMPERFECT_MC_key <> FALSE) { //a perfect grid match has been found.  Set as new location
        $IMPERFECT_MC_NEW_LOC = $EMPTYLOC_array[$IMPERFECT_MC_key]['LOCATION'];
        $displayarray[$topcostkey]['IMPERFECT_MC_SLOT_LOC'] = $IMPERFECT_MC_NEW_LOC;
        $IMPERFECT_MC_NEW_GRD5 = $EMPTYLOC_array[$IMPERFECT_MC_key]['DIMGROUP'];
        $displayarray[$topcostkey]['AssgnGrid5'] = $IMPERFECT_MC_NEW_GRD5; //Add new grid5 to display array
        $NEW_GRD_HGT = $EMPTYLOC_array[$IMPERFECT_MC_key]['HIGH'];
        $NEW_GRD_DPT = $EMPTYLOC_array[$IMPERFECT_MC_key]['DEEP'];
        $NEW_GRD_WDT = $EMPTYLOC_array[$IMPERFECT_MC_key]['WIDE'];
        $imperfectbay = $EMPTYLOC_array[$IMPERFECT_MC_key]['WALKFEET'];


        $caseoreachTF = _caseoreachtf($IMPERF_MC_TIER); //call function to determine which TF function to access.  Using the tier of the empty grid to determine which tf (case or each) to use
        if ($PCCHEI * $PCCLEN * $PCCWID * $PCCPKU > 0) { //if there is a case pkgu and all dimensions are set
            if ($caseoreachTF == 'Case') {
                //use case tf function
                $NEW_LOC_TRUEFIT_array = _truefitgrid2iterations_case($IMPERF_MC_GRID5, $NEW_GRD_HGT, $NEW_GRD_DPT, $NEW_GRD_WDT, $PCLIQU, $PCCHEI, $PCCLEN, $PCCWID, $PCCPKU);  //call funcation to calculate TF based of grid5
            } else {
                //use each tf function
                $NEW_LOC_TRUEFIT_array = _truefitgrid2iterations($IMPERF_MC_GRID5, $NEW_GRD_HGT, $NEW_GRD_DPT, $NEW_GRD_WDT, $PCLIQU, $PCEHEI, $PCELEN, $PCEWID);
            }
        } else {
            $NEW_LOC_TRUEFIT_array = _truefitgrid2iterations($IMPERF_MC_GRID5, $NEW_GRD_HGT, $NEW_GRD_DPT, $NEW_GRD_WDT, $PCLIQU, $PCEHEI, $PCELEN, $PCEWID);
        }

        $NEW_LOC_TRUEFIT_round2 = $NEW_LOC_TRUEFIT_array[1]; //assign 2-iteration tf to variable
// START - New Logic
        $Newmin = _minloc($NEW_LOC_TRUEFIT_round2, $TOP_REPLEN_COST_array[$topcostkey]['SHIP_QTY_MN'], $PCCPKU);
        $impmoves_after_imperfectMC = _implied_daily_moves($NEW_LOC_TRUEFIT_round2, $Newmin, $TOP_REPLEN_COST_array[$topcostkey]['AVG_DAILY_UNIT'], $TOP_REPLEN_COST_array[$topcostkey]['AVG_INV_OH'], $TOP_REPLEN_COST_array[$topcostkey]['SHIP_QTY_MN'], $TOP_REPLEN_COST_array[$topcostkey]['AVGD_BTW_SLE']);
        $replen_score_imperfectMC = _replen_score_from_moves($impmoves_after_imperfectMC);


        if ($zone == 'CSE') { //calculate LSE or CSE walk cost
            $walk_score_imperfectMC_array = _walkcost_case($VCTTIR, $VCTTIR, $TOP_REPLEN_COST_array[$topcostkey]['AVG_DAILY_UNIT'], $TOP_REPLEN_COST_array[$topcostkey]['FLOOR']);
            $walk_score_imperfectMC = $walk_score_imperfectMC_array['WALK_SCORE'];
        } else {
            $walk_score_imperfectMC = _walkscore($value_l2, $OPT_OPTBAY, $TOP_REPLEN_COST_array[$topcostkey]['AVG_DAILY_PICK']);
        }


        $displayarray[$topcostkey]['AFTER_IMPERFECT_MC_SLOT_MOVES'] = $impmoves_after_imperfectMC;
        $displayarray[$topcostkey]['MOVESCORE_AFTER_IMPERFECT_MC'] = abs($replen_score_imperfectMC);
        $displayarray[$topcostkey]['WALKSCORE_AFTER_IMPERFECT_MC'] = abs($walk_score_imperfectMC);
        $displayarray[$topcostkey]['TOTSCORE_AFTER_IMPERFECT_MC'] = abs($replen_score_imperfectMC) * abs($walk_score_imperfectMC);


        unset($EMPTYLOC_array[$IMPERFECT_MC_key]);
        $EMPTYLOC_array = array_values($EMPTYLOC_array);
        break;
    } else {
        $displayarray[$topcostkey]['AFTER_IMPERFECT_MC_SLOT_MOVES'] = '-';
        $displayarray[$topcostkey]['MOVESCORE_AFTER_IMPERFECT_MC'] = '-';
        $displayarray[$topcostkey]['WALKSCORE_AFTER_IMPERFECT_MC'] = '-';
        $displayarray[$topcostkey]['TOTSCORE_AFTER_IMPERFECT_MC'] = '-';
    }
}
