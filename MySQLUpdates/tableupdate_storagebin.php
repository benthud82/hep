
<?php

include_once '../connection/connection_details.php';
include_once '../globalfunctions/slottingfunctions.php';

ini_set('memory_limit', '-1'); //max size 32m
$filename = 'storage_bin_HHP_20180509.txt';  //need to change this to look for any date
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


$sqldelete3 = "TRUNCATE hep.slotmaster ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

//insert into slotmaster table
$columns = 'slotmaster_branch,slotmaster_loc,slotmaster_level,slotmaster_locdesc, slotmaster_grhigh,slotmaster_grdeep,slotmaster_grwide,slotmaster_grcube,slotmaster_usehigh,slotmaster_usedeep,slotmaster_usewide,slotmaster_usecube,slotmaster_pkgu,slotmaster_pickzone,slotmaster_dimgroup,slotmaster_tier,slotmaster_fixt,slotmaster_fixt_desc,slotmaster_stor,slotmaster_stor_desc';
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
        $slotmaster_branch = $result[$counter]['LagerNr'];
        $slotmaster_loc = $result[$counter]['Lagerplatz'];
        $slotmaster_level = substr($slotmaster_loc, 0, 1);
        $slotmaster_locdesc = $result[$counter]['Lagerbereich'];
        $slotmaster_grhigh =  str_replace(',', '.', ($result[$counter]['Hoehe']));
        $slotmaster_grdeep = str_replace(',', '.', ($result[$counter]['Laenge']));
        $slotmaster_grwide = str_replace(',', '.', ($result[$counter]['Breite']));
        $slotmaster_grcube = str_replace(',', '.', ($result[$counter]['Volumen']));
        $slotmaster_usehigh =  str_replace(',', '.', ($result[$counter]['Hoehe']));
        $slotmaster_usedeep = str_replace(',', '.', ($result[$counter]['Laenge']));
        $slotmaster_usewide = str_replace(',', '.', ($result[$counter]['Breite']));
        $slotmaster_usecube = str_replace(',', '.', ($result[$counter]['Volumen']));
        $slotmaster_pkgu = '1';
        $slotmaster_pickzone = $result[$counter]['Kommibreich'];
        $slotmaster_dimgroup = $result[$counter]['Platztyp'];
        $slotmaster_fixt = $result[$counter]['LMFIXT'];
        $slotmaster_fixt_desc = $result[$counter]['LMFIXT_DESC'];
        $slotmaster_stor = $result[$counter]['LMSTGT'];
        $slotmaster_stor_desc = $result[$counter]['LMSTGT_DESC'];
        $slotmaster_tier = _tiercalc($slotmaster_fixt, $slotmaster_stor, $slotmaster_loc, $slotmaster_locdesc);

        //need to add locked location data here:


        $data[] = "('$slotmaster_branch', '$slotmaster_loc', '$slotmaster_level', '$slotmaster_locdesc', '$slotmaster_grhigh', '$slotmaster_grdeep', '$slotmaster_grwide', '$slotmaster_grcube', "
                . "'$slotmaster_usehigh', '$slotmaster_usedeep', '$slotmaster_usewide', '$slotmaster_usecube', '$slotmaster_pkgu', '$slotmaster_pickzone', '$slotmaster_dimgroup',  '$slotmaster_tier',"
                . "'$slotmaster_fixt', '$slotmaster_fixt_desc', '$slotmaster_stor', '$slotmaster_stor_desc')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO hep.slotmaster ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop