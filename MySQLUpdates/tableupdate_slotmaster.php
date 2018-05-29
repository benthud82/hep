
<?php

include_once '../connection/connection_details.php';
include_once '../globalfunctions/slottingfunctions.php';

ini_set('memory_limit', '-1'); //max size 32m
ini_set('max_execution_time', 99999);

$fileglob = glob('../../ftproot/ftpde/storage_bin*.txt');  //glob wildcard searches for any file

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


$sqldelete3 = "TRUNCATE hep.slotmaster ";
$querydelete3 = $conn1->prepare($sqldelete3);
$querydelete3->execute();

//insert into slotmaster table
$columns = 'slotmaster_branch,slotmaster_loc,slotmaster_level,slotmaster_locdesc, slotmaster_grhigh,slotmaster_grdeep,slotmaster_grwide,slotmaster_grcube,slotmaster_usehigh,slotmaster_usedeep,slotmaster_usewide,slotmaster_usecube,slotmaster_pkgu,slotmaster_pickzone,slotmaster_dimgroup,slotmaster_tier,slotmaster_fixt,slotmaster_fixt_desc,slotmaster_stor,slotmaster_stor_desc, slotmaster_distance, slotmaster_bay, slotmaster_walkbay';
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
        $slotmaster_branch = $result[$counter]['Warehouse'];
        $slotmaster_loc = $result[$counter]['StorageBin'];
        $slotmaster_level = substr($slotmaster_loc, 0, 1);
        $slotmaster_locdesc = $result[$counter]['StorageSection'];
        $slotmaster_grhigh = str_replace(',', '.', ($result[$counter]['Height']));
        $slotmaster_grdeep = str_replace(',', '.', ($result[$counter]['Length']));
        $slotmaster_grwide = str_replace(',', '.', ($result[$counter]['Width']));
        $slotmaster_grcube = str_replace(',', '.', ($result[$counter]['Volume']));
        $slotmaster_usehigh = str_replace(',', '.', ($result[$counter]['Height']));
        $slotmaster_usedeep = str_replace(',', '.', ($result[$counter]['Length']));
        $slotmaster_usewide = str_replace(',', '.', ($result[$counter]['Width']));
        $slotmaster_usecube = str_replace(',', '.', ($result[$counter]['Volume']));
        $slotmaster_pkgu = '1';
        $slotmaster_pickzone = $result[$counter]['PickingArea'];
        $slotmaster_dimgroup = $result[$counter]['StorageTyp'];
        $slotmaster_fixt = $result[$counter]['LMFIXT'];
        $slotmaster_fixt_desc = $result[$counter]['LMFIXT_DESC'];
        $slotmaster_stor = $result[$counter]['LMSTGT'];
        $slotmaster_stor_desc = $result[$counter]['LMSTGT_DESC'];
        $slotmaster_distance = intval($result[$counter]['Distance']) * 2;  //multiply by 2 to get round trip distance
        $slotmaster_tier = _tiercalc($slotmaster_fixt, $slotmaster_stor, $slotmaster_loc, $slotmaster_locdesc);

        $slotmaster_bay_return = _baycalc($slotmaster_loc, $slotmaster_tier);
           //The bay cannot be determined from the location.  Use bay_loc table to update the bay at the bottom of this update
        $slotmaster_bay = ' ';
        $slotmaster_walkbay = $slotmaster_bay_return[1];



        //need to add locked location data here:


        $data[] = "('$slotmaster_branch', '$slotmaster_loc', '$slotmaster_level', '$slotmaster_locdesc', '$slotmaster_grhigh', '$slotmaster_grdeep', '$slotmaster_grwide', '$slotmaster_grcube', "
                . "'$slotmaster_usehigh', '$slotmaster_usedeep', '$slotmaster_usewide', '$slotmaster_usecube', '$slotmaster_pkgu', '$slotmaster_pickzone', '$slotmaster_dimgroup',  '$slotmaster_tier',"
                . "'$slotmaster_fixt', '$slotmaster_fixt_desc', '$slotmaster_stor', '$slotmaster_stor_desc', $slotmaster_distance, '$slotmaster_bay', '$slotmaster_walkbay')";
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

foreach ($fileglob as $deletefile) {
    unlink(realpath($deletefile));
}


//Pull in vector map bay from bay_loc and overwrite $slotmaster_bay in the slotmaster table
    $sqlmerge2 = "INSERT INTO hep.slotmaster  (SELECT  slotmaster_branch, 
slotmaster_loc,  
slotmaster_level,
slotmaster_locdesc, 
slotmaster_grhigh,
slotmaster_grdeep,  
slotmaster_grwide,  
slotmaster_grcube,  
slotmaster_usehigh,  
slotmaster_usedeep,  
slotmaster_usewide,  
slotmaster_usecube,  
slotmaster_pkgu,  
slotmaster_pickzone,  
slotmaster_dimgroup,  
slotmaster_tier,  
slotmaster_fixt,  
slotmaster_fixt_desc,  
slotmaster_stor,  
slotmaster_stor_desc,  
slotmaster_distance,  
bayloc_bay,
bayloc_walkbay
FROM hep.slotmaster JOIN hep.bay_location on slotmaster_loc = bayloc_loc)
                                    ON DUPLICATE KEY UPDATE 
                                    slotmaster_bay=VALUES(slotmaster_bay), slotmaster_walkbay=VALUES(slotmaster_walkbay)";
    $querymerge2 = $conn1->prepare($sqlmerge2);
    $querymerge2->execute();
