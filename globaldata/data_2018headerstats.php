<?php
include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$today = date('Y-m-d');
$month = date('m');



$hdr_movecountsql = $conn1->prepare("SELECT 
                                                                                    COUNT(*) as CURMNTHMOVES
                                                                                FROM
                                                                                    hep.itemsmoved_2018goal
                                                                                WHERE
                                                                                    MONTH(goal_movedate) = $month
                                                                                        AND goal_whse = $var_whse;");
$hdr_movecountsql->execute();
$hdr_movecountsqlarray = $hdr_movecountsql->fetchAll(pdo::FETCH_ASSOC);
$movecount = $hdr_movecountsqlarray[0]['CURMNTHMOVES'];

if($movecount >= 100){
    $movecolor = 'green-jungle';
}else{
    $movecolor = 'red-intense';
}


$hdr_yearincrease = $conn1->prepare("SELECT 
                                                                            TRUNCATE(AVG(SCORE_TOTALSCORE - (SELECT 
                                                                                    histitem_totalscore
                                                                                FROM
                                                                                    hep.slottingscore_hist_item B
                                                                                WHERE
                                                                                    histitem_item = A.goal_item
                                                                                        AND histitem_whse = A.goal_whse
                                                                                        AND histitem_date = A.goal_movedate
                                                                                ORDER BY histitem_date DESC
                                                                                LIMIT 1)) * 100,2) AS YEARTOTAL
                                                                        FROM
                                                                            hep.itemsmoved_2018goal A
                                                                                JOIN
                                                                            hep.slottingscore ON goal_whse = SCORE_WHSE
                                                                                AND goal_item = SCORE_ITEM
                                                                                AND goal_pkgu = SCORE_PKGU
                                                                                WHERE
                                                                                        goal_whse = $var_whse;");
$hdr_yearincrease->execute();
$hdr_yearincrease_array = $hdr_yearincrease->fetchAll(pdo::FETCH_ASSOC);
$yearincrease= $hdr_yearincrease_array[0]['YEARTOTAL'];

if(is_null($yearincrease)){
    $yearincrease = 0;
}

if($yearincrease >= 0 ){
    $yearcolor = 'green-jungle';
}else{
    $yearcolor = 'red-intense';
}

$hdr_monthincrease = $conn1->prepare("SELECT 
                                                                            TRUNCATE(AVG(SCORE_TOTALSCORE - (SELECT 
                                                                                    histitem_totalscore
                                                                                FROM
                                                                                    hep.slottingscore_hist_item B
                                                                                WHERE
                                                                                    histitem_item = A.goal_item
                                                                                        AND histitem_whse = A.goal_whse
                                                                                        AND histitem_date = A.goal_movedate
                                                                                ORDER BY histitem_date DESC
                                                                                LIMIT 1)) * 100,2) AS MONTHTOTAL
                                                                        FROM
                                                                            hep.itemsmoved_2018goal A
                                                                                JOIN
                                                                            hep.slottingscore ON goal_whse = SCORE_WHSE
                                                                                AND goal_item = SCORE_ITEM
                                                                                AND goal_pkgu = SCORE_PKGU
                                                                                WHERE
                                                                                    MONTH(goal_movedate) = $month
                                                                                        AND goal_whse = $var_whse;");
$hdr_monthincrease->execute();
$hdr_monthincrease_array = $hdr_monthincrease->fetchAll(pdo::FETCH_ASSOC);
$monthincrease= $hdr_monthincrease_array[0]['MONTHTOTAL'];

if(is_null($monthincrease)){
    $monthincrease = 0;
}

if($monthincrease >= 0 ){
    $monthcolor = 'green-jungle';
}else{
    $monthcolor = 'red-intense';
}


?>

<div class="row" style="padding-top: 25px">
    <div class="col-lg-4 " id="stat_totaltime">
        <div class="dashboard-stat dashboard-stat-v2 <?php echo $movecolor?>">  
            <div class="visual">
                <i class="fa fa-info-circle"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $movecount ?></span>
                </div>
                <div class="desc"> Total Moves this Month</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 " id="stat_totaltime">
        <div class="dashboard-stat dashboard-stat-v2 <?php echo $monthcolor?>">  
            <div class="visual">
                <i class="fa fa-info-circle"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $monthincrease ?></span>
                </div>
                <div class="desc">Monthly Score Increase/Decrease</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 " id="stat_totaltime">
        <div class="dashboard-stat dashboard-stat-v2 <?php echo $yearcolor?>">  
            <div class="visual">
                <i class="fa fa-info-circle"></i>
            </div>
            <div class="details">
                <div class="number">
                    <span data-counter="counterup" data-value="1349"><?php echo $yearincrease ?></span>
                </div>
                <div class="desc">Yearly Score Increase/Decrease</div>
            </div>
        </div>
    </div>

</div>