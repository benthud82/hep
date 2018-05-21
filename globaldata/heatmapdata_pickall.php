
<?php
include_once '../connection/connection_details.php';


$table = "vectormap";
ini_set('max_execution_time', 99999);

date_default_timezone_set('Europe/London');

$var_zone = $_POST['zonesel'];  //pulled from heatmap.php
$var_levelsel = $_POST['levelsel'];  //pulled from heatmap.php
$var_datesel = date("Y-m-d", strtotime($_POST['datesel']));  //pulled from heatmap.php


if ($var_zone == 'L%') {


    $baycolor = $conn1->prepare("SELECT 
    BAY,
    YPOS,
    XPOS,
    BAYHEIGHT,
    BAYWIDTH,
    CASE
        WHEN BAY LIKE 'NON%' THEN 'GRAY'
        WHEN BAY LIKE 'NOT%' THEN 'GRAY'
        WHEN BAY LIKE 'BLACK' THEN 'BLACK'
        WHEN BAY LIKE 'BF0100' THEN '#BF0100'
        WHEN BAY LIKE 'C84007' THEN '#C84007'
        WHEN BAY LIKE 'D1800F' THEN '#D1800F'
        WHEN BAY LIKE 'DAC118' THEN '#DAC118'
        WHEN BAY LIKE 'C4E321' THEN '#C4E321'
        WHEN BAY LIKE 'GREEN' THEN 'GREEN'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) >= 30
        THEN
            'BLACK'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) >= 25
        THEN
            '#BF0100'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) >= 12
        THEN
            '#C84007'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) >= 7
        THEN
            '#D1800F'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) >= 3
        THEN
            '#DAC118'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) >= 1
        THEN
            '#C4E321'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) <= 0
        THEN
            'WHITESMOKE'
        WHEN
            SUM(CASE
                WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
                ELSE 0
            END) IS NULL
        THEN
            'WHITESMOKE'
        ELSE 'GREEN'
    END AS SCORECOLOR,
    SUM(CASE
        WHEN optbayhist_date = '$var_datesel' THEN optbayhist_pick
        ELSE 0
    END) AS TOTPICK,
    SUM(optbayhist_count) AS COUNT
FROM
    hep.vectormap
        LEFT JOIN
    hep.optimalbay_hist ON optbayhist_bay = BAY
WHERE
    CSLS = 'LSE' AND LEVEL = '$var_levelsel'
GROUP BY BAY , YPOS , XPOS , BAYHEIGHT , BAYWIDTH;");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $baycolor->execute();
    $baycolorarray = $baycolor->fetchAll(pdo::FETCH_ASSOC);
} else {


    $baycolor = $conn1->prepare("SELECT 
                                vectormap . *,
                                BAY,
                                case
                                    when BAY like 'NON%' then 'GRAY'
                                    when BAY like 'NOT%' then 'GRAY'
                                    when BAY  like 'BLACK' then 'BLACK'
                                    when BAY  like 'BF0100' then '#BF0100'
                                    when BAY  like 'C84007' then '#C84007'
                                    when BAY  like 'D1800F' then '#D1800F'
                                    when BAY  like 'DAC118' then '#DAC118'
                                    when BAY  like 'C4E321' then '#C4E321'
                                    when BAY  like 'GREEN' then 'GREEN'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) >= 30 then 'BLACK'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) >= 25 then '#BF0100'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) >= 12 then '#C84007'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) >= 7 then '#D1800F'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) >= 3 then '#DAC118'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) >= 1 then '#C4E321'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) <= 0 then 'WHITESMOKE'
                                    when sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) is null then 'WHITESMOKE'
                                    else 'GREEN'
                                end as SCORECOLOR,
                                sum(case when optbayhist_date = '$var_datesel' then optbayhist_pick else 0 end) as TOTPICK,
                                sum(optbayhist_count) as COUNT
                                FROM
                                hep.vectormap
                                    LEFT JOIN
                                hep.optimalbay_hist ON optbayhist_whse = VECTWHSE
                                    and optbayhist_bay = BAY
                                WHERE
                                    VECTWHSE = $var_whse and CSLS = 'CSE' $sparkssql
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
                                TEXT not like '%Replens%' and LEVEL = '$var_levelsel'");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $loosetext->execute();
    $loosetextarray = $loosetext->fetchAll(pdo::FETCH_ASSOC);
} else {
    $loosetext = $conn1->prepare("SELECT 
                                *
                            FROM
                                hep.casetext
                            WHERE
                                WHSE = $var_whse $sparkstextsql");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
    $loosetext->execute();
    $loosetextarray = $loosetext->fetchAll(pdo::FETCH_ASSOC);
}
$screenfactor = 1;

$posfactor = 1;
$heiwidfactor = 5;

include 'heatmapdatecontainer.php';
?>



<div class="borderedcontainer">
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
</div>
