<?php
$levelpick = $conn1->prepare("SELECT 
                                                        slotmaster_level, SUM(AVG_DAILY_PICK) as LEVEL_PICK, SUM(AVG_DAILY_UNIT) as LEVEL_UNIT
                                                    FROM
                                                        hep.nptsld
                                                            JOIN
                                                        hep.item_location ON ITEM = loc_item
                                                            JOIN
                                                        hep.slotmaster ON loc_location = slotmaster_loc
                                                        WHERE slotmaster_level = '$var_levelsel'
                                                    GROUP BY slotmaster_level");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$levelpick->execute();
$levelpick_array = $levelpick->fetchAll(pdo::FETCH_ASSOC);


$zonepick = $conn1->prepare("SELECT 
                                                            SUBSTR(slotmaster_loc, 1, 2) AS ZONE,
                                                            SUM(AVG_DAILY_PICK) as ZONE_PICK,
                                                            SUM(AVG_DAILY_UNIT) as ZONE_UNIT
                                                        FROM
                                                            hep.nptsld
                                                                JOIN
                                                            hep.item_location ON ITEM = loc_item
                                                                JOIN
                                                            hep.slotmaster ON loc_location = slotmaster_loc
                                                            WHERE slotmaster_level = '$var_levelsel'
                                                        GROUP BY SUBSTR(slotmaster_loc, 1, 2)");  //$orderby pulled from: include 'slopecat_switch_orderby.php';
$zonepick->execute();
$zonepick_array = $zonepick->fetchAll(pdo::FETCH_ASSOC);
?>



<!--Picks by Level and Zone-->
<div class="row">
        <div class="col-lg-6">
            <div class="widget-thumb widget-bg-color-white">
                <div class="widget-thumb-wrap">
                    <!--<i class="widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>-->
                    <div class="widget-thumb-body">
                        <span class="widget-thumb-body-stat" >Total Picks for Level <?php echo ($levelpick_array[0]['slotmaster_level']) ?> : <?php echo intval($levelpick_array[0]['LEVEL_PICK']) ?> </span>
                    </div>
                </div>
            </div>
        </div>
</div>

<div class="row">
    <?php foreach ($zonepick_array as $zonekey => $value) { ?>
        <div class="col-lg-2">
            <div class="widget-thumb widget-bg-color-white text-uppercase">
                <div class="widget-thumb-wrap">
                    <!--<i class="widget-thumb-icon bg-blue fa  fa-clock-o  fa-3x "></i>-->
                    <div class="widget-thumb-body">
                        <span class="widget-thumb-subtitle"><?php echo $zonepick_array[$zonekey]['ZONE'] ?> Picks:</span>
                        <span class="widget-thumb-body-stat" ><?php echo intval($zonepick_array[$zonekey]['ZONE_PICK']) ?> </span>
                    </div>
                </div>
            </div>
        </div>

    <?php } ?>
</div>
