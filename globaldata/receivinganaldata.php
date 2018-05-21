
<?php
include_once '../../globalincludes/usa_asys_session.php';
include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$itemnum = $_POST['itemnum'];
$datesel = intval(1 . date('ymd', strtotime($_POST['datesel'])));

$receipts = $aseriesconn->prepare("SELECT * FROM HSIPCORDTA.NPFERA WHERE EAWHSE = $var_whse and EASEQ3 = 1 and EATRND = $datesel and EAITEM = '$itemnum' ORDER BY EATRNT ASC");
$receipts->execute();
$receiptsarray = $receipts->fetchAll(pdo::FETCH_ASSOC);

$whslocprim = $conn1->prepare("SELECT S.*, AVG_DAILY_UNIT FROM hep.whse_status S  JOIN hep.my_npfmvc on WAREHOUSE = LOWHSE and ITEM_NUMBER = LOITEM and PACKAGE_UNIT = LOPKGU WHERE LOWHSE = $var_whse and LOITEM = $itemnum and LOMAXC > 0 order by LOPKGU asc");
$whslocprim->execute();
$whslocprimarray = $whslocprim->fetchAll(pdo::FETCH_ASSOC);

$whslocres = $conn1->prepare("SELECT * FROM hep.whse_status WHERE LOWHSE = $var_whse and LOITEM = $itemnum and LOMAXC = 0 order by LOLOC asc");
$whslocres->execute();
$whslocresarray = $whslocres->fetchAll(pdo::FETCH_ASSOC);


//What actually happened for the receipts?  How were they split?  What locations did they go to?
?>
<div class="row">
    <div  class='col-sm-12 col-md-12 col-lg-6 ' >
        <div class="panel">
            <div class="panel-heading" style="font-size: large; font-weight: 700;">Primary Locations </div>
            <div class='widget-content widget-table' >
                <div class='divtable'>
                    <div class='divtableheader'>
                        <div class='divtabletitle width12_5' style="cursor: default">LOCATION</div>
                        <div class='divtabletitle width12_5' style="cursor: default">PKGU</div>
                        <div class='divtabletitle width12_5' style="cursor: default">ON HAND</div>
                        <div class='divtabletitle width12_5' style="cursor: default">MAX QTY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">MIN QTY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">MOVE QTY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">REC QTY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">AVG. DLY. UNIT</div>
                    </div>
                    <?php foreach ($whslocprimarray as $key => $value) { ?>
                        <div class='divtablerow itemdetailexpand'>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LOLOC']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LOPKGU']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LOONHD']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LOMAXC']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LOMINC']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LOPMTQ']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocprimarray[$key]['LORCVQ']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo number_format(trim($whslocprimarray[$key]['AVG_DAILY_UNIT']),2); ?> </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>    

    <div  class='col-sm-12 col-md-12 col-lg-6'>
        <div class="panel">
            <div class="panel-heading" style="font-size: large;  font-weight: 700;" >Reserve Locations </div>
            <div class='widget-content widget-table' >
                <div class='divtable'>
                    <div class='divtableheader'>
                        <div class='divtabletitle width12_5' style="cursor: default">LOCATION</div>
                        <div class='divtabletitle width12_5' style="cursor: default">LOC VOLUME</div>
                        <div class='divtabletitle width12_5' style="cursor: default">VOLUME OPEN</div>
                        <div class='divtabletitle width12_5' style="cursor: default">EXP. DAY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">EXP. MONTH</div>
                        <div class='divtabletitle width12_5' style="cursor: default">EXP. YEAR</div>
                        <div class='divtabletitle width12_5' style="cursor: default">REC QTY</div>
                    </div>
                    <?php foreach ($whslocresarray as $key => $value) { ?>
                        <div class='divtablerow itemdetailexpand'>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LOLOC']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LOVOLM']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LOVOLO']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LOEXDY']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LOEXMO']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LOEXYR']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($whslocresarray[$key]['LORCVQ']); ?> </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>    
</div>


<?php
//Receipt transactions for this day.
?>
<div class="row">
    <div  class='col-sm-12 col-md-12 col-lg-6 ' >
        <div class="panel">
            <div class="panel-heading" style="font-size: large; font-weight: 700; background-color: #5191d1; color: white;">Receipt Transactions</div>
            <div class='widget-content widget-table' >
                <div class='divtable'>
                    <div class='divtableheader'>
                        <div class='divtabletitle width12_5' style="cursor: default">TIME</div>
                        <div class='divtabletitle width12_5' style="cursor: default">TSM</div>
                        <div class='divtabletitle width12_5' style="cursor: default">PO</div>
                        <div class='divtabletitle width12_5' style="cursor: default">LOCATION</div>
                        <div class='divtabletitle width12_5' style="cursor: default">PUR QTY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">QTY</div>
                        <div class='divtabletitle width12_5' style="cursor: default">LOT#</div>
                        <div class='divtabletitle width12_5' style="cursor: default">EXP DATE</div>

                    </div>
                    <?php foreach ($receiptsarray as $key => $value) { ?>
                        <div class='divtablerow itemdetailexpand'>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EATRNT']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EATRNE']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EAPONM']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EATLOC']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EAPURQ']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EATRNQ']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EALOT#']); ?> </div>
                            <div class='divtabledata width12_5'> <?php echo trim($receiptsarray[$key]['EAEXPD']); ?> </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>    

</div>


<?php


//What should have happened to the receipts?  Call receiving function so it can be looped later.

