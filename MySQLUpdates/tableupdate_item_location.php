<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
ini_set('max_execution_time', 99999);

$fileglob = glob('../../ftproot/ftpde/slot_master_HHP*.txt');  //glob wildcard searches for any file

if(count($fileglob) > 0){
    $filename = $fileglob[0];
}

$result = array();
$fp = fopen($filename, 'r');
if (($headers = fgetcsv($fp, 0, "\t")) !== FALSE) {
    if ($headers) {
        while (($line = fgetcsv($fp, 0, "\t")) !== FALSE) {
            if ($line) {
                if (sizeof($line) == sizeof($headers)) {
                    $result[] = array_combine($headers, $line);
                }
            }
        }
    }
}
fclose($fp);


$sqldelete3 = "TRUNCATE hep.item_location ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

//insert into item_location table
$columns = 'loc_branch, loc_location, loc_level, loc_item, loc_truefit, loc_slotqty, loc_minqty, loc_itemdesc';
$maxrange = 9999;
$counter = 0;
$rowcount = count($result);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $loc_branch = $result[$counter]['Warehouse'];
        $loc_location = $result[$counter]['StorageBin'];
        $loc_level = substr($loc_location, 0,1);
        $loc_item = intval($result[$counter]['Material']);
        $loc_truefit = str_replace(',', '.', intval($result[$counter]['BinQuantityMax']));
        $loc_slotqty = str_replace(',', '.', intval($result[$counter]['ReplenishQuantity']));
        $loc_minqty = intval($loc_slotqty * .25);
        $loc_itemdesc = 'N/A';

        $data[] = "('$loc_branch', '$loc_location', '$loc_level',$loc_item, $loc_truefit, $loc_slotqty,$loc_minqty, '$loc_itemdesc' )";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO hep.item_location ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop

foreach ($fileglob as $deletefile) {
    unlink(realpath($deletefile));
}