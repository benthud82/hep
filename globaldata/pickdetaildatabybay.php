
<?php
ini_set('max_execution_time', 99999);

include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$var_bay = $_POST['baycode'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];
if ($var_whse > 10) {
    include_once '../../globalincludes/newcanada_asys.php';
} else {
    include_once '../../globalincludes/usa_asys.php';
}

$var_date = date('mdy', strtotime($_POST['datesel']));


$result1 = $aseriesconn->prepare("SELECT PDITEM, PDLOC#, count(*) as TOTPICKS FROM A.HSIPCORDTA.NOTWPT WHERE PDSHPD = $var_date and substring(PDLOC#,1,5) = '$var_bay' GROUP BY PDITEM, PDLOC# ORDER BY count(*) desc");
$result1->execute();
$result1array = $result1->fetchAll(pdo::FETCH_ASSOC);
?>


<!--start of div table-->
<div class="" id="divtablecontainer">
    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div class='divtableheader'>
                    <div class='divtabletitle width15' data-toggle='tooltip' title='Click on item for item query' data-placement='top' data-container='body' style="cursor: default">Item</div>
                    <div class='divtabletitle width15' style="cursor: default">Location</div>
                    <div class='divtabletitle width15' style="cursor: default">Pick Count</div>
                </div>
                <?php foreach ($result1array as $key => $value) { ?>
                    <div class='divtablerow itemdetailexpand'>

                            <!--<div class='divtabledata width10 '><i class="fa fa-plus-square fa-lg " style="cursor: pointer;"  data-toggle="tooltip" data-placement="top" title="" data-original-title="Toggle Detail"></i></div>-->
                        <div class='divtabledata width15' ><a href="itemquery.php?itemnum=<?php echo $result1array[$key]['PDITEM']; ?>&userid=<?php echo $var_userid ?>" target="_blank"><?php echo $result1array[$key]['PDITEM']; ?></a></div>
                        <div class='divtabledata width15'> <?php echo $result1array[$key]['PDLOC#']; ?> </div>
                        <div class='divtabledata width15'> <?php echo $result1array[$key]['TOTPICKS']; ?> </div>
                    </div>

                <?php } ?>
            </div>
        </div>

    </div>    
</div>    

