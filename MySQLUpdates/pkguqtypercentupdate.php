<?php

//updates table hep.pkgu_percent

ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include '../connection/connection_details.php';
$autoid = 0;

$sqldelete = "TRUNCATE TABLE hep.pkgu_percent";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$columns = 'idpkgu_percent, PERC_WHSE, PERC_ITEM, PERC_PKGU, PERC_PKGTYPE, PERC_SHIPQTY, PERC_PERC';


$cpcresult = $conn1->prepare("SELECT 
                                                    a.ITEM,
                                                    1,
                                                    CASE
                                                        WHEN a.PKTYPE IN ('101' , '111') THEN 'LSE'
                                                        ELSE 'CSE'
                                                    END as PACKAGE_TYPE,
                                                    SUM(UNITS) AS TOTQTY,
                                                    SUM(UNITS) / (SELECT 
                                                            SUM(UNITS)
                                                        FROM
                                                            hep.hep_raw_30day t
                                                        WHERE
                                                            t.ITEM = a.ITEM) AS PERC_PERC
                                                FROM
                                                    hep.hep_raw_30day a
                                                GROUP BY a.ITEM , 1 , CASE
                                                    WHEN a.PKTYPE IN ('101' , '111') THEN 'LSE'
                                                    ELSE 'CSE'
                                                END");
$cpcresult->execute();
$NPFCPC_ALL_array = $cpcresult->fetchAll(pdo::FETCH_ASSOC);


$maxrange = 9999;
$counter = 0;
$rowcount = count($NPFCPC_ALL_array);

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 5,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item

        $ITEM_NUMBER = intval($NPFCPC_ALL_array[$counter]['ITEM']);

        $PACKAGE_UNIT = intval(1);
        $PACKAGE_TYPE = $NPFCPC_ALL_array[$counter]['PACKAGE_TYPE'];
        $PERC_SHIPQTY = $NPFCPC_ALL_array[$counter]['TOTQTY'];
        $PERC_PERC = $NPFCPC_ALL_array[$counter]['PERC_PERC'];
        if ($PERC_PERC === NULL) {
            $PERC_PERC = 1;
        }


        $data[] = "($autoid, 'HEP', $ITEM_NUMBER, $PACKAGE_UNIT, '$PACKAGE_TYPE', '$PERC_SHIPQTY', '$PERC_PERC')";
        $counter += 1;
    }


    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT INTO hep.pkgu_percent ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 10000;
} while ($counter <= $rowcount); //end of item by whse loop




