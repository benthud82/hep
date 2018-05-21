<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
$filename = 'item_master_HHP_20180509.txt';  //need to change this to look for any date
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


$sqldelete3 = "TRUNCATE hep.npfcpcsettings ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

//insert into npfcpcsettings table
$columns = 'CPCWHSE, CPCITEM, CPCEPKU, CPCCPKU, CPCFLOW, CPCTOTE, CPCSHLF, CPCROTA, CPCESTK, CPCLIQU, CPCPFRL, CPCEWID, CPCEHEI, CPCELEN, CPCCWID, CPCCHEI, CPCCLEN, CPCNEST, CPCCONV';
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
        $CPCWHSE = $result[$counter]['LagerNr'];
        $CPCITEM = str_replace(',', '.', intval($result[$counter]['Material']));
        $CPCEPKU = str_replace(',', '.', intval($result[$counter]['ST_Pack_Mg']));
        $CPCCPKU = str_replace(',', '.', intval($result[$counter]['GEB_Pack_Mg']));
        $CPCFLOW = " ";
        $CPCTOTE = " ";
        $CPCSHLF = " ";
        $CPCROTA = $result[$counter]['Kann_Liegend'];
        $CPCESTK = 0;
        $CPCLIQU = " ";
        $CPCPFRL = $result[$counter]['Pick_Reserve'];
        $CPCEWID = str_replace(',', '.', intval($result[$counter]['ST_Breite']));
        $CPCEHEI = str_replace(',', '.', intval($result[$counter]['ST_Hoehe']));
        $CPCELEN = str_replace(',', '.', intval($result[$counter]['ST_Laenge']));
        $CPCCWID = str_replace(',', '.', intval($result[$counter]['GEB_Breite']));
        $CPCCHEI = str_replace(',', '.', intval($result[$counter]['GEB_Hoehe']));
        $CPCCLEN = str_replace(',', '.', intval($result[$counter]['GEB_Laenge']));
        $CPCNEST = 0;
        $CPCCONV = $result[$counter]['Conveyable'];



        $data[] = "('$CPCWHSE', $CPCITEM, $CPCEPKU, $CPCCPKU, '$CPCFLOW', '$CPCTOTE', '$CPCSHLF', '$CPCROTA', $CPCESTK, '$CPCLIQU', '$CPCPFRL', '$CPCEWID', '$CPCEHEI', '$CPCELEN', '$CPCCWID', '$CPCCHEI', '$CPCCLEN', $CPCNEST, '$CPCCONV')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO hep.npfcpcsettings ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop