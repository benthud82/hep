
<?php
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');

include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$var_item = $_POST['itemcode'];
$var_lseorcse = $_POST['lseorcse'];
if ($var_lseorcse == 'moveauditclicklse') {
    $zonesql = ' and MVTZNE <= 6';
} else {
    $zonesql = ' and MVTZNE > 6';
}

$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];
if ($var_whse > 10) {
    include_once '../../globalincludes/newcanada_asys.php';
} else {
    include_once '../../globalincludes/usa_asys.php';
}

$startdate = date('Ymd', strtotime('-90 days'));

//detail data query
$result1 = $aseriesconn->prepare("SELECT MVTITM, MVTYPE, MVFLC#, MVTLC#, date(substr(MVCNFD,1,4) || '-' || substr(MVCNFD,5,2) || '-' || substr(MVCNFD,7,2)) as DATE,  MVREQT, MVREQQ, MVCNFQ FROM A.HSIPCORDTA.NPFMVE WHERE (MVTPKG <> 0) and MVCNFQ<>0 and (MVDESC like 'COMPLETED%' or MVDESC like 'MAN%') and MVCNFD >= $startdate and MVTITM = '$var_item' and MVWHSE = $var_whse  $zonesql GROUP BY MVTITM, MVTYPE, MVFLC#, MVTLC#, date(substr(MVCNFD,1,4) || '-' || substr(MVCNFD,5,2) || '-' || substr(MVCNFD,7,2)),  MVREQT, MVREQQ, MVCNFQ ORDER BY  date(substr(MVCNFD,1,4) || '-' || substr(MVCNFD,5,2) || '-' || substr(MVCNFD,7,2)) DESC");
$result1->execute();
$result1array = $result1->fetchAll(pdo::FETCH_ASSOC);

//summary data query
$result2 = $aseriesconn->prepare("SELECT sum(case WHEN MVTYPE = 'RS' then 1 else 0 end) as AUTOS, sum(case WHEN MVTYPE = 'SK' or MVTYPE = 'SJ' or MVTYPE = 'SP' or MVTYPE = 'SO' then 1 else 0 end) as ASOS,  sum(case WHEN MVTYPE = 'CM' then 1 else 0 end) as CONSOLS from A.HSIPCORDTA.NPFMVE WHERE (MVTPKG <> 0) and MVCNFQ<>0 and (MVDESC like 'COMPLETED%' or MVDESC like 'MAN%') and MVCNFD >= $startdate and MVTITM = '$var_item' and MVWHSE = $var_whse  $zonesql ");
$result2->execute();
$result2array = $result2->fetchAll(pdo::FETCH_ASSOC);
?>
<div class="" id="divtablecontainer">
<!--start of div for summary data-->
<div class="row">
    <div class="col-lg-3"> <div class="h5"><?php echo 'AUTOs: ' . $result2array[0]['AUTOS']; ?> </div> </div>
    <div class="col-lg-3"> <div class="h5"><?php echo 'ASOs: ' . $result2array[0]['ASOS']; ?> </div> </div>
    <div class="col-lg-3"> <div class="h5"><?php echo 'CONSOLs: ' . $result2array[0]['CONSOLS']; ?> </div> </div>
    <div class="col-lg-3"> <div class="h5"><?php echo 'Est. Yearly Moves: ' . ($result2array[0]['AUTOS'] + $result2array[0]['ASOS'] + $result2array[0]['CONSOLS']) * 4; ?> </div> </div>
</div>


<!--start of div table for detail data-->

    <div  class='col-sm-12 col-md-12 col-lg-12 print-1wide'  style="float: none;">

        <div class='widget-content widget-table'  style="position: relative;">
            <div class='divtable'>
                <div class='divtableheader'>
                    <div class='divtabletitle width12_5' style="cursor: default">Item</div>
                    <div class='divtabletitle width12_5' style="cursor: default">Type</div>
                    <div class='divtabletitle width12_5' style="cursor: default">From Loc</div>
                    <div class='divtabletitle width12_5' style="cursor: default">To Loc</div>
                    <div class='divtabletitle width12_5' style="cursor: default">Req. Date</div>
                    <div class='divtabletitle width12_5' style="cursor: default">Req. Time</div>
                    <div class='divtabletitle width12_5' style="cursor: default">Req. Qty.</div>
                    <div class='divtabletitle width12_5' style="cursor: default">Conf. Qty.</div>
                </div>
<?php foreach ($result1array as $key => $value) { ?>
                    <div class='divtablerow itemdetailexpand'>

                                <!--<div class='divtabledata width10 '><i class="fa fa-plus-square fa-lg " style="cursor: pointer;"  data-toggle="tooltip" data-placement="top" title="" data-original-title="Toggle Detail"></i></div>-->
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVTITM']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVTYPE']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVFLC#']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVTLC#']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['DATE']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVREQT']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVREQQ']; ?> </div>
                        <div class='divtabledata width12_5'> <?php echo $result1array[$key]['MVCNFQ']; ?> </div>
                    </div>

<?php } ?>
            </div>
        </div>

    </div>    
</div>    

