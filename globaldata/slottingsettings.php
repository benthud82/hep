<?php
if (isset($displayarray)) {
    $settingscount = 0;  //initialize counter to determine if at least one issue is met
    $settingsmax = $displayarray[$key2]['CURMAX'];
    $settingsmin = $displayarray[$key2]['CURMIN'];


    $PCSHLF = $displayarray[$key2]['OKinShelf'];
    $PCFLOR = $displayarray[$key2]['OKinFlow'];
    $PCTOTE = $displayarray[$key2]['OKinTote'];
    $PCEROT = $displayarray[$key2]['Rotate'];
    $PCLIQU = $displayarray[$key2]['Liquid'];
    $PCESTA = $displayarray[$key2]['Stack'];
    $PCPFRC = $displayarray[$key2]['PFRC'];
    $PCCPKU = $displayarray[$key2]['CasePkgu'];

    $mintomaxcalc = $settingsmin / $settingsmax;
    $optimalmin = _minloc($settingsmax, $displayarray[$key2]['SHIP_QTY_MN'], $PCCPKU);
    
    
} else {
    $settingscount = 0;  //initialize counter to determine if at least one issue is met
    $PCSHLF = $itemsettingsarray[0]['CPCSHLF'];
    $PCFLOR = $itemsettingsarray[0]['CPCFLOW'];
    $PCTOTE = $itemsettingsarray[0]['CPCTOTE'];
    $PCEROT = $itemsettingsarray[0]['CPCROTA'];
    $PCLIQU = $itemsettingsarray[0]['CPCLIQU'];
    $PCESTA = $itemsettingsarray[0]['CPCESTK'];
    $PCPFRC = $itemsettingsarray[0]['CPCPFRC'];
    $PCPFRA = $itemsettingsarray[0]['CPCPFRA'];
    $PCCPKU = $itemsettingsarray[0]['CPCCPKU'];


    $settingsmax = $itemcostarray[0]['Curr_Max'];
    $settingsmin = $itemcostarray[0]['Curr_Min'];
    $mintomaxcalc = $settingsmin / $settingsmax;
    $optimalmin = _minloc($settingsmax, $displayarray[$key2]['SHIP_QTY_MN'], $PCCPKU);
}
?>

<div class="col-lg-12 bordered" style="padding-bottom: 5px;">
    <div class="portlet light" style="padding: 0px"> 
        <div class="portlet-title">
            <div class="caption caption-md">
                <span class="caption-subject bold uppercase">Item Settings Analysis</span>
            </div>
        </div>
        <div class="media-body">

            <?php if ($mintomaxcalc > .3) { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Adj. min to <?php echo $optimalmin ?> (from <?php echo $settingsmin ?>) </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>

            <?php if ($PCSHLF == 'N') { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check OK in Shelf Setting: <?php echo $PCSHLF ?> </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>


            <?php if ($PCFLOR == 'N') { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check OK in Flow Setting: <?php echo $PCFLOR ?></div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>


            <?php if ($PCTOTE == 'N') { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check OK in Tote Setting: <?php echo $PCTOTE ?> </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>


            <?php if ($PCEROT == 'N') { ?> 
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check Rotation Setting: <?php echo $PCTOTE ?> </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>


            <?php if ($PCLIQU <> "  ") { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check Liquid Setting: <?php echo $PCLIQU ?> </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>


            <?php if ($PCESTA > 0) { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check Stackable Setting: <?php echo $PCESTA ?> </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>



            <?php if ($PCCPKU > 1 && $PCPFRC <> 'Y') { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> Check PFR Setting: <?php echo 'DC: ' . $PCPFRC; ?> </div> 
                <?php
                $settingscount += 1; //add to settings count
            }
            ?>
            <?php if ($settingscount == 0) { ?>
                <div class="col-sm-12" style="padding-bottom: 5px;"><i class="fa fa-arrow-right"></i> There are no settings issues! </div> 
                <?php } ?>

                
        </div>
    </div>
</div>
