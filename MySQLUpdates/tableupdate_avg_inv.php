<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
$filename = 'inventory_DBP_20180509.txt';  //need to change this to look for any date
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


$sqldelete3 = "TRUNCATE hep.avg_inv ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

//insert into inventory table

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
        $ITEM = intval($result[$counter]['MatNr']);
        $YEAR = intval($result[$counter]['Jahr']);
        $MONTH = intval($result[$counter]['Monat']);
        $AVG_OH = str_replace(',', '.', intval($result[$counter]['Bestand']));

        $data[] = "($ITEM, $YEAR, $MONTH, $AVG_OH)";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO hep.avg_inv (ITEM, YEAR, MONTH, AVG_OH) VALUES $values ON DUPLICATE KEY UPDATE AVG_OH=values(AVG_OH)";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop