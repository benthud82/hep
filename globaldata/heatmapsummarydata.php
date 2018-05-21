
<?php
ini_set('max_execution_time', 99999);
//include_once '../globalincludes/ustxgpslotting_mysql.php';
include_once '../globalincludes/nahsi_mysql.php';
//include_once '../globalincludes/usa_asys.php';

$today = date('Y-m-d');
$var_zone = $_POST['zonesel'];  //pulled from heatmap.php
$var_whse = $_POST['whsesel'];  //pulled from heatmap.php


$colorcount = $conn1->prepare("SELECT 
                                costhist_date as HISTDATE,
                                count(case
                                    when costhist_cost >= 20 then 1
                                end) as BLACKCOUNT,
                                count(case
                                    when costhist_cost >= 15 and costhist_cost < 20 then 1
                                end) as REDCOUNT,
                                count(case
                                    when costhist_cost >= 10 and costhist_cost < 15 then 1
                                end) as ORANGECOUNT,
                                count(case
                                    when costhist_cost >= 5 and costhist_cost < 10 then 1
                                end) as YELLOWCOUNT,
                                count(case
                                    when costhist_cost < 5 then 1
                                end) as GREENCOUNT
                            FROM
                                hep.slottingcost_hist
                            WHERE
                                costhist_whse = $var_whse
                                    and substring(costhist_tier, 1, 1) like '$var_zone'
                                    and costhist_date = '$today'
                                    and costhist_tier in ('L02', 'L04')
                            GROUP BY costhist_date;");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$colorcount->execute();
$colorcountarray = $colorcount->fetchAll(pdo::FETCH_ASSOC);

$black = $colorcountarray[0]['BLACKCOUNT'];
$red = $colorcountarray[0]['REDCOUNT'];
$orange = $colorcountarray[0]['ORANGECOUNT'];
$yellow = $colorcountarray[0]['YELLOWCOUNT'];
$green = $colorcountarray[0]['GREENCOUNT'];
$totalcount = $black + $red + $orange + $yellow + $green;
?>

<div class="row">
    <div class="col-sm-2">
        <div id="blackcount" class="uppercase profile-stat-title">
            <?php echo $black ?>
        </div>
        <div class="uppercase profile-stat-text">
            Black
        </div>
    </div>
    <div class="col-sm-2">
        <div id="redcount" class="uppercase profile-stat-title">
            <?php echo $red ?>
        </div>
        <div class="uppercase profile-stat-text">
            Red
        </div>
    </div>
    <div class="col-sm-2">
        <div id="orangecount" class="uppercase profile-stat-title">
            <?php echo $orange ?>
        </div>
        <div class="uppercase profile-stat-text">
            Orange
        </div>
    </div>
    <div class="col-sm-2">
        <div id="yellowcount" class="uppercase profile-stat-title">
            <?php echo $yellow ?>
        </div>
        <div class="uppercase profile-stat-text">
            Yellow
        </div>
    </div>
    <div class="col-sm-2">
        <div id="greencount" class="uppercase profile-stat-title">
            <?php echo $green ?>
        </div>
        <div class="uppercase profile-stat-text">
            Green
        </div>
    </div>
    <div class="col-sm-2">
        <div id="totalcount" class="uppercase profile-stat-title">
            <?php echo $totalcount ?>
        </div>
        <div class="uppercase profile-stat-text">
            Total
        </div>
    </div>
</div>        






