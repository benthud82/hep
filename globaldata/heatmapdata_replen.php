
<?php
include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$var_levelsel = $_POST['levelsel'];  //pulled from heatmap.php


ini_set('max_execution_time', 99999);

date_default_timezone_set('Europe/London');

//
//$datesql = $conn1->prepare("SELECT 
//                                max(replen_date) as recentdate
//                            FROM
//                                hep.replen_hist
//                            WHERE
//                                replen_whse = $var_whse;");
//$datesql->execute();
//$datesqlarary = $datesql->fetchAll(pdo::FETCH_ASSOC);
//$today = $datesqlarary[0]['recentdate'];
//
//
$var_zone = $_POST['zonesel'];  //pulled from heatmap.php
$var_datesel = date("Y-m-d", strtotime($_POST['datesel']));  //pulled from heatmap.php


if ($var_zone == 'L%') {


    $baycolor = $conn1->prepare("SELECT 
    vectormap . *,
    BAY,
    case
        when BAY like 'NON%' then 'GRAY'
        when BAY  like 'BLACK' then 'BLACK'
        when BAY  like 'RED' then 'RED'
        when BAY  like 'ORANG' then 'ORANGE'
        when BAY  like 'YELLO' then 'YELLOW'
        when BAY  like 'GREEN' then 'GREEN'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 30
        then
            'BLACK'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 25
        then
            'RED'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 12
        then
            'ORANGE'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 7
        then
            'YELLOW'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) <= 0
        then
            'WHITESMOKE'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) is null
        then
            'WHITESMOKE'
        else 'GREEN'
    end as SCORECOLOR,
    sum(case
        when replen_date = '$var_datesel' then replen_replens
        else 0
    end) as YEARLYREPLEN
FROM
    hep.vectormap
        LEFT JOIN
    hep.replen_hist ON replen_bay = BAY
WHERE
    CSLS = 'LSE' "
            . " and LEVEL = '$var_levelsel' GROUP BY BAY");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
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
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 30
        then
            'BLACK'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 25
        then
            'RED'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 12
        then
            'ORANGE'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) >= 7
        then
            'YELLOW'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) <= 0
        then
            'WHITESMOKE'
        when
            sum(case
                when replen_date = '$var_datesel' then replen_replens
                else 0
            end) is null
        then
            'WHITESMOKE'
        else 'GREEN'
    end as SCORECOLOR,
    sum(case
        when replen_date = '$var_datesel' then replen_replens
        else 0
    end) as YEARLYREPLEN
FROM
    hep.vectormap
        LEFT JOIN
    hep.replen_hist ON replen_whse = VECTWHSE
        and replen_bay = BAY
WHERE
    VECTWHSE = $var_whse and CSLS = 'CSE' and LEVEL = 
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
                            TEXT not like '%Picks%'");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
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
            <rect id="<?php echo $baycolorarray[$key]['BAY'] ?>" class="clickablesvg" x="<?php echo $baycolorarray[$key]['XPOS'] * $screenfactor ?>" y="<?php echo $baycolorarray[$key]['YPOS'] * $screenfactor ?>" width="<?php echo $baycolorarray[$key]['BAYWIDTH'] * $screenfactor ?>" height="<?php echo $baycolorarray[$key]['BAYHEIGHT'] * $screenfactor ?>" style="stroke:#464646; fill: <?php echo $baycolorarray[$key]['SCORECOLOR'] ?> "><title><?php echo $baycolorarray[$key]['BAY'] . ': ' . $baycolorarray[$key]['YEARLYREPLEN'] . ' Addtl Yearly Replens' ?></title></rect>
                <?php } ?>
    </svg>
</div>