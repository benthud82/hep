
<?php
include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

if($var_whse == 3){
    $sparkssql = " and (BAY >= 'W4000' or BAY like 'NON%')";
} else{
    $sparkssql = " ";
}


ini_set('max_execution_time', 99999);

date_default_timezone_set('America/Chicago');

$today = date('Y-m-d');
$var_zone = $_POST['zonesel'];  //pulled from heatmap.php


if ($var_zone == 'L%') {


    $baycolor = $conn1->prepare("SELECT 
                                optbayhist_bay as BAY,
                                case
                                    when optbayhist_pick >= 30 then 'BLACK'
                                    when optbayhist_pick >= 25 then 'RED'
                                    when optbayhist_pick >= 12 then 'ORANGE'
                                    when optbayhist_pick >= 7 then 'YELLOW'
                                    else 'GREEN'
                                end as SCORECOLOR,
                                optbayhist_pick as TOTPICK,
                                YPOS,
                                XPOS,
                                BAYHEIGHT,
                                BAYWIDTH
                            FROM
                                hep.optimalbay_hist,
                                hep.vectormap
                            WHERE
                                optbayhist_whse = $var_whse
                                    and substring(optbayhist_tier, 1, 1) like '$var_zone'
                                    and optbayhist_whse = VECTWHSE
                                    -- and optbayhist_date = '$today'
                                    and optbayhist_date = '2016-12-05'
                                    and optbayhist_bay = BAY
                            GROUP BY optbayhist_bay;");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $baycolor->execute();
    $baycolorarray = $baycolor->fetchAll(pdo::FETCH_ASSOC);
} else {


    $baycolor = $conn1->prepare("SELECT 
                                    vectormap . *,
                                    BAY,
                                    case
                                        when BAY like 'NON%' then 'GRAY'
                                        when
                                            sum(case
                                                when optbayhist_date = '2016-12-08' then optbayhist_pick
                                                else 0
                                            end) >= 30
                                        then
                                            'BLACK'
                                        when
                                            sum(case
                                                when optbayhist_date = '2016-12-08' then optbayhist_pick
                                                else 0
                                            end) >= 25
                                        then
                                            'RED'
                                        when
                                            sum(case
                                                when optbayhist_date = '2016-12-08' then optbayhist_pick
                                                else 0
                                            end) >= 12
                                        then
                                            'ORANGE'
                                        when
                                            sum(case
                                                when optbayhist_date = '2016-12-08' then optbayhist_pick
                                                else 0
                                            end) >= 7
                                        then
                                            'YELLOW'
                                        when
                                            sum(case
                                                when optbayhist_date = '2016-12-08' then optbayhist_pick
                                                else 0
                                            end) <= 0
                                        then
                                            'WHITESMOKE'
                                        when
                                            sum(case
                                                when optbayhist_date = '2016-12-08' then optbayhist_pick
                                                else 0
                                            end) is null
                                        then
                                            'WHITESMOKE'
                                        else 'GREEN'
                                    end as SCORECOLOR,
                                    sum(optbayhist_pick) as TOTPICK,
                                    sum(optbayhist_count) as COUNT
                                FROM
                                    hep.vectormap
                                        LEFT JOIN
                                    hep.optimalbay_hist ON optbayhist_whse = VECTWHSE
                                        and optbayhist_bay = BAY
                                        LEFT JOIN
                                    hep.case_floor_locs ON VECTWHSE = WHSE
                                        and substring(LOCATION, 1, 5) = BAY
                                WHERE
                                    VECTWHSE = $var_whse and CSLS = 'CSE'
                                GROUP BY BAY;");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $baycolor->execute();
    $baycolorarray = $baycolor->fetchAll(pdo::FETCH_ASSOC);
}





if ($var_zone == 'L%') {
    $loosetext = $conn1->prepare("SELECT 
                                *
                            FROM
                                hep.loosetext
                            WHERE
                                WHSE = $var_whse");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $loosetext->execute();
    $loosetextarray = $loosetext->fetchAll(pdo::FETCH_ASSOC);
} else {
    $loosetext = $conn1->prepare("SELECT 
                                *
                            FROM
                                hep.casetext
                            WHERE
                                WHSE = $var_whse");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $loosetext->execute();
    $loosetextarray = $loosetext->fetchAll(pdo::FETCH_ASSOC);
}
$screenfactor = 1;

$posfactor = 1;
$heiwidfactor = 5;
?>
<svg id="svg2" width="100%" height="100%" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" >
    <?php
    //Populate text if checkbox is checked.
    foreach ($loosetextarray as $key => $value) {
        ?>
        <text transform = "translate(<?php echo $loosetextarray[$key]['XTRANS'] . ', ' . $loosetextarray[$key]['YTRANS'] . ') rotate(' . $loosetextarray[$key]['ROTATE'] . ')' ?>" font-family="'Open Sans', sans-serif" font-size="<?php echo $loosetextarray[$key]['FONTSIZE'] ?>" ><?php echo $loosetextarray[$key]['TEXT'] ?></text>
    <?php } ?>

    <?php foreach ($baycolorarray as $key => $value) { ?>
        <rect id="<?php echo $baycolorarray[$key]['BAY'] ?>" class="clickablesvg" x="<?php echo $baycolorarray[$key]['XPOS'] * $screenfactor ?>" y="<?php echo $baycolorarray[$key]['YPOS'] * $screenfactor ?>" width="<?php echo $baycolorarray[$key]['BAYWIDTH'] * $screenfactor ?>" height="<?php echo $baycolorarray[$key]['BAYHEIGHT'] * $screenfactor ?>" style="stroke:#464646; fill: <?php echo $baycolorarray[$key]['SCORECOLOR'] ?> "><title><?php echo $baycolorarray[$key]['BAY'] . ': ' . $baycolorarray[$key]['TOTPICK'] . ' Picks' ?></title></rect>
            <?php } ?>
</svg>
