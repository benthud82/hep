<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
ini_set('max_execution_time', 99999);
$fileglob = glob('../../ftproot/ftpde/picking_to*.txt');  //glob wildcard searches for any file

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
        $WHSE = $result[$counter]['Warehouse'];
        $ITEM = intval($result[$counter]['Material']);
        $INVOICE = intval($result[$counter]['StoBin_dest']);
        $PKGU = intval($result[$counter]['Package_quant']);
        $PKTYPE = intval($result[$counter]['StoType_from']);
        $LOCATION = $result[$counter]['StoBin_from'];
        $UNITS = str_replace(',', '.', intval($result[$counter]['Quantity_put']));
        $PICKDATE = date('Y-m-d', strtotime($result[$counter]['Post_date']));


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

foreach ($fileglob as $deletefile) {
    unlink(realpath($deletefile));
}