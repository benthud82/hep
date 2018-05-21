<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
$filename = 'picking_to_HHP_20180509.txt';  //need to change this to look for any date
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


//insert into item_location table
$columns = 'idsales, WHSE, ITEM, INVOICE, PKGU, PKTYPE, LOCATION, UNITS, PICKDATE';
$maxrange = 9999;
$counter = 0;
$rowcount = count($result);
$idsales = 0;
do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
        $WHSE = $result[$counter]['LagerNr'];
        $ITEM = intval($result[$counter]['MatNr']);
        $INVOICE = intval($result[$counter]['Nach_LgPla']);
        $PKGU = intval($result[$counter]['Gebinde_Menge']);
        $PKTYPE = intval($result[$counter]['Von_LgTyp']);
        $LOCATION = $result[$counter]['Von_LgPla'];
        $UNITS = str_replace(',', '.', intval($result[$counter]['Nach_IstMg']));
        $PICKDATE = date('Y-m-d', strtotime($result[$counter]['Buch_Dat']));


        $data[] = "($idsales, '$WHSE',  $ITEM, $INVOICE, $PKGU, $PKTYPE, '$LOCATION', $UNITS, '$PICKDATE')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO hep.hep_raw ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop