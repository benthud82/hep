
<?php
ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';


$var_item = $_POST['itemnum'];  //pulled from itemquery.php
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];




$itemdetail_loose = $conn1->prepare("SELECT DISTINCT
    A.WAREHOUSE,
    A.ITEM_NUMBER,
    A.PACKAGE_UNIT,
    A.PACKAGE_TYPE,
    A.CUR_LOCATION,
    A.DAYS_FRM_SLE,
    A.AVGD_BTW_SLE,
    A.NBR_SHIP_OCC,
    A.AVG_INV_OH,
    A.PICK_QTY_MN,
    A.SHIP_QTY_MN,
    A.DLY_CUBE_VEL,
    A.DLY_PICK_VEL,
    A.LMGRD5,
    A.LMDEEP,
    A.LMTIER,
    A.SUGGESTED_TIER,
    A.SUGGESTED_GRID5,
    A.SUGGESTED_DEPTH,
    A.SUGGESTED_MAX,
    A.SUGGESTED_MIN,
    A.SUGGESTED_SLOTQTY,
    A.SUGGESTED_IMPMOVES,
    A.CURRENT_IMPMOVES,
    A.SUGGESTED_NEWLOCVOL,
    A.SUGGESTED_DAYSTOSTOCK,
    A.AVG_DAILY_PICK,
    A.AVG_DAILY_UNIT,
    B.OPT_NEWGRIDVOL,
    B.OPT_PPCCALC,
    B.OPT_OPTWALKFEET,
    B.OPT_CURRWALKFEET,
    B.OPT_CURRDAILYFT,
    B.OPT_SHLDDAILYFT,
    B.OPT_ADDTLFTPERPICK,
    B.OPT_ADDTLFTPERDAY,
    B.OPT_WALKCOST,
    loc_truefit AS CURMAX,
    loc_minqty AS CURMIN,
    loc_truefit AS VCCTRF,
    E.SCORE_TOTALSCORE,
    E.SCORE_REPLENSCORE,
    E.SCORE_WALKSCORE,
    loc_itemdesc AS ITEM_DESC
FROM
    hep.my_npfmvc A
        JOIN
    hep.optimalbay B ON A.ITEM_NUMBER = B.OPT_ITEM
        JOIN
    hep.item_location ON A.ITEM_NUMBER = loc_item and loc_location = A.CUR_LOCATION
        JOIN
    hep.slottingscore E ON E.SCORE_ITEM = A.ITEM_NUMBER
WHERE
    ITEM_NUMBER = $var_item
        AND PACKAGE_TYPE = 'LSE' LIMIT 1");
$itemdetail_loose->execute();
$itemdetailarray_loose = $itemdetail_loose->fetchAll(pdo::FETCH_ASSOC);


//Loose Display
foreach ($itemdetailarray_loose as $key => $value) {
    $CorL = 'Loose';
    ?>
    <div class="row"> 
        <div class="" style="padding-bottom: 5px;">
            <section class="panel">
                <header class="panel-heading bg bg-inverse h3"> Item Slotting Detail | <?php echo $CorL . ' Pkgu of ' . $itemdetailarray_loose[$key]['PACKAGE_UNIT'] ?> | <?php echo $itemdetailarray_loose[$key]['ITEM_DESC']; ?></header>
                <div class="media"> 
                    <div class="row">
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $itemdetailarray_loose[$key]['ITEM_NUMBER'] ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Item Code</div>
                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $itemdetailarray_loose[$key]['CUR_LOCATION'] ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Location</div>
                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $itemdetailarray_loose[$key]['LMGRD5'] ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Current Grid5</div>
                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo number_format(($itemdetailarray_loose[$key]['SCORE_REPLENSCORE'] * 100), 2) . '%' ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Replen Score</div>

                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo number_format(($itemdetailarray_loose[$key]['SCORE_WALKSCORE'] * 100), 2) . '%' ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Walk Score</div>

                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo number_format(($itemdetailarray_loose[$key]['SCORE_TOTALSCORE'] * 100), 2) . '%' ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Total Score</div>
                            <div class="col-sm-12 h5" style="padding-bottom: 4px;"><i class="fa fa-2x clicktotoggle-chevron fa-chevron-circle-down" style="padding: 0px 0px 0px 20px; float: right; cursor: pointer;"></i></div>
                        </div>
                    </div> 
                </div> 
                <!--Start of hidden data-->
                <div class="hiddencostdetail panel-body" style="display: none;">
                    <div class="row" style="padding-bottom: 10px; padding-top: 10px;">
                        <!--Customer Scorecard Panels for month/quarter/rolling 12-->
                        <div class="col-lg-3 col-sm-6 panel-no-page-break">
                            <!-- Current Month Panel -->            
                            <section class="panel">
                                <header class="panel-heading bg  h3 text-center bg-softblue">Replen Stats </header>
                                <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                    <div class="widget-content-blue-wrapper changed-up">
                                        <div class="widget-content-blue-inner padded">
                                            <div class="h4"><i class="fa fa-sitemap"></i> Replen Score</div>
                                            <div class="line m-l m-r"></div> 
                                            <div class="value-block">
                                                <!--Replen Score-->
                                                <div class="h2" id="score_replen"><?php echo number_format(($itemdetailarray_loose[$key]['SCORE_REPLENSCORE'] * 100), 2) . '%' ?></div> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- List group -->
                                <div class="list-group">
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo '  ' . intval($itemdetailarray_loose[$key]['CURRENT_IMPMOVES'] * 253) ?></strong></span> 
                                        Current Yearly Moves
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo intval($itemdetailarray_loose[$key]['SUGGESTED_IMPMOVES'] * 253) ?></strong></span> 
                                        Optimal Yearly Moves
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['SUGGESTED_GRID5'] ?></strong></span> 
                                        Suggested Grid5
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['SUGGESTED_DEPTH'] ?></strong></span> 
                                        Suggested Depth
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['LMGRD5'] ?></strong></span> 
                                        Current Grid5
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['LMDEEP'] ?></strong></span> 
                                        Current Depth
                                    </div>

                                </div>
                            </section>
                        </div>
                        <div class="col-lg-3 col-sm-6 panel-no-page-break">
                            <!-- Current Quarter Panel -->            
                            <section class="panel"> 
                                <header class="panel-heading bg h3 text-center bg-softblue">Travel Stats </header>
                                <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                    <div class="widget-content-blue-wrapper changed-up">
                                        <div class="widget-content-blue-inner padded">
                                            <div class="h4"><i class="fa fa-blind"></i> Travel Score</div>
                                            <div class="line m-l m-r"></div> 
                                            <div class="value-block">
                                                <!--Walk Score-->
                                                <div class="h2" id="score_quarter"><?php echo number_format(($itemdetailarray_loose[$key]['SCORE_WALKSCORE'] * 100), 2) . '%' ?></div> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- List group -->
                                <div class="list-group">
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['OPT_CURRWALKFEET'] ?></strong></span> 
                                        Current Walk Meters
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['OPT_OPTWALKFEET'] ?></strong></span> 
                                        Optimal Walk Meters
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo number_format($itemdetailarray_loose[$key]['OPT_ADDTLFTPERDAY'], 1) ?></strong></span> 
                                        Additional Meters / Day
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><i id="pickauditclicklse" class="fa fa-question-circle pickauditclick" style="cursor: pointer; text-decoration: none;"></i><?php echo '  ' . number_format($itemdetailarray_loose[$key]['AVG_DAILY_PICK'], 2) ?></strong></span> 
                                        Avg. Daily Pick
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo number_format($itemdetailarray_loose[$key]['AVG_DAILY_UNIT'], 2) ?></strong></span> 
                                        Avg. Daily Units
                                    </div>
                                    <div class="list-group-item"> 
                                        <span class="pull-right"><strong><?php echo number_format($itemdetailarray_loose[$key]['OPT_PPCCALC'], 2) ?></strong></span> 
                                        Picks Per Cubic CM
                                    </div>

                                </div>
                            </section>
                        </div>
                        <div class="col-lg-6 col-sm-12 panel-no-page-break">
                            <!-- Current R12 Panel -->            
                            <section class="panel"> 
                                <header class="panel-heading bg h3 text-center bg-softgreen">Slotting Details </header>
                                <div class="panel-body  text-center" style="border-bottom: 3px solid #ccc;">
                                    <div class="widget-content-blue-wrapper changed-up">
                                        <div class="widget-content-blue-inner padded">
                                            <div class="h4"><i class="fa fa-info-circle"></i> Total Score</div>
                                            <div class="line m-l m-r"></div> 
                                            <div class="value-block">
                                                <!--Total Score-->
                                                <div class="h2" id="score_quarter"><?php echo number_format(($itemdetailarray_loose[$key]['SCORE_TOTALSCORE'] * 100), 2) . '%' ?></div> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- List group -->
                                <div class="list-group">
                                    <div class="row">
                                        <div class="col-md-6 bordered">
                                            <div class="list-group-item "> 
                                                <span class="pull-right"><strong><?php echo number_format($itemdetailarray_loose[$key]['AVGD_BTW_SLE'], 1) ?></strong></span> 
                                                Avg. Days Between Sale
                                            </div>
                                            <div class="list-group-item "> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['DAYS_FRM_SLE'] ?></strong></span> 
                                                Days Since Last Sale 
                                            </div>
                                            <div class="list-group-item "> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['NBR_SHIP_OCC'] ?></strong></span> 
                                                Ship Occurrences
                                            </div>
                                            <div class="list-group-item "> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['AVG_INV_OH'] ?></strong></span> 
                                                Avg Inventory OH
                                            </div>
                                            <div class="list-group-item "> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['PICK_QTY_MN'] ?></strong></span> 
                                                Pick Quantity Mean
                                            </div>
                                            <div class="list-group-item "> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['SHIP_QTY_MN'] ?></strong></span> 
                                                Ship Qty Mean
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><i id="tierdescclick" class="fa fa-question-circle tierdescclick" style="cursor: pointer; text-decoration: none;"></i><?php echo '  ' .  $itemdetailarray_loose[$key]['LMTIER'] ?></strong></span> 
                                                Current Tier
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><i id="tierdescclick" class="fa fa-question-circle tierdescclick" style="cursor: pointer; text-decoration: none;"></i><?php echo '  ' .  $itemdetailarray_loose[$key]['SUGGESTED_TIER'] ?></strong></span> 
                                                Suggested Tier
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['CURMAX'] ?></strong></span> 
                                                Current Max
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['SUGGESTED_MAX'] ?></strong></span> 
                                                Suggested Max
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['CURMIN'] ?></strong></span> 
                                                Current Min
                                            </div>
                                            <div class="list-group-item"> 
                                                <span class="pull-right"><strong><?php echo $itemdetailarray_loose[$key]['SUGGESTED_MIN'] ?></strong></span> 
                                                Suggested Min
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>



                </div>
                <!--End of hidden data-->

            </section>
        </div>
    </div>



    <?php
}

