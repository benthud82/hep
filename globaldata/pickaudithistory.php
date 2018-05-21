
<?php
include_once '../connection/connection_details.php';
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

$var_item = $_POST['itemcode'];

$startdate = date('Ymd', strtotime('-90 days'));

//detail data query
$result1 = $conn1->prepare("SELECT 
                                                                INVOICE, PICKDATE, PKTYPE, LOCATION, PKGU, UNITS
                                                            FROM
                                                                hep.hep_raw
                                                            WHERE
                                                                ITEM = $var_item AND PKTYPE IN ('101' , '111') and PICKDATE >= $startdate
                                                            ORDER BY PICKDATE DESC");
$result1->execute();
$result1array = $result1->fetchAll(pdo::FETCH_ASSOC);

//summary data query
$result2 = $conn1->prepare("SELECT 
                                                    COUNT(*) AS PICKCOUNT
                                                FROM
                                                    hep.hep_raw
                                                WHERE
                                                    ITEM = $var_item AND PKTYPE IN ('101' , '111') and PICKDATE >= $startdate
                                                ORDER BY PICKDATE DESC");
$result2->execute();
$result2array = $result2->fetchAll(pdo::FETCH_ASSOC);
?>
<div class="" id="divtablecontainer_pick">
<!--start of div for summary data-->
<div class="row">
    <div class="col-lg-3"> <div class="h5"><?php echo 'Pick Count: ' . $result2array[0]['PICKCOUNT']; ?> </div> </div>
    <div class="col-lg-3"> <div class="h5"><?php echo 'Est. Yearly Picks: ' . ($result2array[0]['PICKCOUNT']) * 4; ?> </div> </div>
    <div class="col-lg-3"> <div class="h5"><?php echo 'Est. Daily Picks: ' . number_format(($result2array[0]['PICKCOUNT']) / (64.28),2); ?> </div> </div>
</div>


<!--start of div table for detail data-->

    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div class='divtableheader'>
                    <div class='divtabletitle width16_66' style="cursor: default">Invoice#</div>
                    <div class='divtabletitle width16_66' style="cursor: default">Order Date</div>
                    <div class='divtabletitle width16_66' style="cursor: default">Pkgu Type</div>
                    <div class='divtabletitle width16_66' style="cursor: default">Pick Location</div>
                    <div class='divtabletitle width16_66' style="cursor: default">Pkgu</div>
                    <div class='divtabletitle width16_66' style="cursor: default">Ship Quantity</div>
                </div>
<?php foreach ($result1array as $key => $value) { ?>
                    <div class='divtablerow itemdetailexpand'>

                                <!--<div class='divtabledata width10 '><i class="fa fa-plus-square fa-lg " style="cursor: pointer;"  data-toggle="tooltip" data-placement="top" title="" data-original-title="Toggle Detail"></i></div>-->
                        <div class='divtabledata width16_66'> <?php echo trim($result1array[$key]['INVOICE']); ?> </div>
                        <div class='divtabledata width16_66'> <?php echo trim($result1array[$key]['PICKDATE']); ?> </div>
                        <div class='divtabledata width16_66'> <?php echo trim($result1array[$key]['PKTYPE']); ?> </div>
                        <div class='divtabledata width16_66'> <?php echo trim($result1array[$key]['LOCATION']); ?> </div>
                        <div class='divtabledata width16_66'> <?php echo trim($result1array[$key]['PKGU']); ?> </div>
                        <div class='divtabledata width16_66'> <?php echo trim($result1array[$key]['UNITS']); ?> </div>
                    </div>

<?php } ?>
            </div>
        </div>

    </div>    
</div>    

