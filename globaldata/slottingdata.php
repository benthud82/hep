
<?php
ini_set('max_execution_time', 99999);
include_once '../connection/connection_details.php';

$var_whse = $_POST['whsesel'];
$var_zone = $_POST['zonesel'];
$var_report = $_POST['reportsel'];

switch ($var_report) {  //build sql statement for report
    case 'SWAP':


        break;
    case 'FROM':
        $reportsql = " A.LMTIER = '$var_zone' and A.SUGGESTED_TIER <> '$var_zone'";

        break;
    case 'TO':
        $reportsql = ' SUGGESTED_TIER = ';

        break;
    case 'ALL':


        break;

    default:
        break;
}


$itemdetail = $conn1->prepare("SELECT 
                                    A.WAREHOUSE,
                                    A.ITEM_NUMBER,
                                    A.PACKAGE_UNIT,
                                    A.PACKAGE_TYPE,
                                    A.DSL_TYPE,
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
                                    B.OPT_OPTBAY,
                                    B.OPT_CURRBAY,
                                    B.OPT_CURRDAILYFT,
                                    B.OPT_SHLDDAILYFT,
                                    B.OPT_ADDTLFTPERPICK,
                                    B.OPT_ADDTLFTPERDAY,
                                    B.OPT_WALKCOST,
                                    C.CURMAX,
                                    C.CURMIN,
                                    D.VCCTRF
                                FROM
                                    hep.my_npfmvc A
                                        join
                                    hep.optimalbay B ON A.WAREHOUSE = B.OPT_WHSE
                                        and A.ITEM_NUMBER = B.OPT_ITEM
                                        and A.PACKAGE_UNIT = B.OPT_PKGU
                                        and A.PACKAGE_TYPE = B.OPT_CSLS
                                        join
                                    hep.mysql_npflsm C ON C.LMWHSE = A.WAREHOUSE
                                        and C.LMITEM = A.ITEM_NUMBER
                                        and C.LMTIER = A.LMTIER
                                        join
                                    hep.system_npfmvc D ON D.VCWHSE = A.WAREHOUSE
                                        and D.VCITEM = A.ITEM_NUMBER
                                        and D.VCPKGU = A.PACKAGE_UNIT
                                        and D.VCFTIR = A.LMTIER
                                WHERE
                                     WAREHOUSE = $var_whse
                                     and $reportsql");
$itemdetail->execute();
$itemdetailarray = $itemdetail->fetchAll(pdo::FETCH_ASSOC);

foreach ($itemdetailarray as $key => $value) {
    if (substr($itemdetailarray[$key]['SUGGESTED_TIER'], 0, 1) == 'L') {
        $CorL = 'Loose';
    } else {
        $CorL = 'Case';
    }
    ?>


    <div class="row"> 
        <div class="col-lg-9" style="padding-bottom: 5px;">
            <section class="panel">
                <header class="panel-heading bg bg-inverse"> Item Slotting Detail | <?php echo $CorL . ' Pkgu of ' . $itemdetailarray[$key]['PACKAGE_UNIT'] ?> </header>
                <div class="media"> 
                    <div class="row">
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $itemdetailarray[$key]['ITEM_NUMBER'] ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Item Code</div>
                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $itemdetailarray[$key]['CUR_LOCATION'] ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Location</div>
                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo $itemdetailarray[$key]['LMGRD5'] ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Current Grid5</div>
                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo '$' . 'XXX' ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Replen Cost</div>

                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo '$' . 'XXX' ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">Walk Cost</div>

                        </div>
                        <div class="col-xl-2 col-sm-4 col-xs-12 text-center" style="padding-bottom: 5px;">
                            <div class="col-sm-12 h3" style="padding-bottom: 5px;"> <?php echo '$' . 'XXX' ?></div> 
                            <div class="col-sm-12 text-muted h5" style="padding-bottom: 10px;">ABS Total Cost</div>
                            <div class="col-sm-12 h5" style="padding-bottom: 4px;"><i class="fa fa-2x clicktotoggle-chevron fa-chevron-circle-down" style="padding: 0px 0px 0px 20px; float: right; cursor: pointer;"></i></div>
                        </div>
                    </div> 
                </div> 

                <!--Start of hidden data-->
                <div class="hiddencostdetail" style="padding: 30px 0px; display: none;">
                    <section class="panel-body"> 
                        <article class="media"> 
                            <div class="media-body"> 
                                <div class="row">
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 
                                    <div class="col-sm-6 col-md-6 col-lg-3 col-xl-2 bordered nopadding_bottom">
                                        <div class="media-body"> 
                                            <div class="media-mini text-center"> 
                                                <strong class="h4 pull-left">Curr. Grid5: </strong>
                                                <a href="" class="h4 pull-right bold"><?php echo ' ' . $itemdetailarray[$key]['LMGRD5'] ?></a> 
                                            </div> 

                                        </div>
                                    </div> 

                                </div> 
                            </div> 
                        </article> 
                    </section>
                </div>
                <!--End of hidden data-->

            </section>
        </div>
    </div>



<?php } ?>