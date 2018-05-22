<?php

include_once '../connection/connection_details.php';
ini_set('memory_limit', '-1'); //max size 32m
ini_set('max_execution_time', 99999);

$fileglob = glob('../../ftproot/ftpde/item_master*.txt');  //glob wildcard searches for any file

if (count($fileglob) > 0) {
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
        $CPCWHSE = $result[$counter]['Warehouse'];
        $CPCITEM = str_replace(',', '.', intval($result[$counter]['Material']));
        $CPCEPKU = str_replace(',', '.', intval($result[$counter]['EachUnit']));
        $CPCCPKU = str_replace(',', '.', intval($result[$counter]['CaseUnit']));
        $CPCFLOW = " ";
        $CPCTOTE = " ";
        $CPCSHLF = " ";
        $CPCROTA = $result[$counter]['Rotatable'];
        $CPCESTK = 0;
        $CPCLIQU = " ";
        $CPCPFRL = $result[$counter]['PickReserve'];
        $CPCEWID = str_replace(',', '.', intval($result[$counter]['EachWidth']));
        $CPCEHEI = str_replace(',', '.', intval($result[$counter]['EachHeight']));
        $CPCELEN = str_replace(',', '.', intval($result[$counter]['EachLenght']));
        $CPCCWID = str_replace(',', '.', intval($result[$counter]['CaseWidth']));
        $CPCCHEI = str_replace(',', '.', intval($result[$counter]['CaseHeight']));
        $CPCCLEN = str_replace(',', '.', intval($result[$counter]['CaseLenght']));
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

foreach ($fileglob as $deletefile) {
    unlink(realpath($deletefile));
}
