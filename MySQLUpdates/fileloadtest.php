<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
$filename = 'inventory_DBQ_20180321.txt';
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
        $ITEM = intval($result[$counter]['Material']);
        $AVG_OH = str_replace(',', '.', intval($result[$counter]['Stock']));

        $data[] = "($ITEM, $AVG_OH)";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO hep.avg_inv (ITEM, AVG_OH) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop