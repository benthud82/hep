<?php

//creates table hep.nptsld
$previousitem = NULL;
$holidays = array();
ini_set('max_execution_time', 99999);
ini_set('memory_limit', '-1');
include_once '../connection/connection_details.php';
include_once '../globalfunctions.php';

$sqldelete = "TRUNCATE hep.hep_grouped";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

$sqldelete2 = "TRUNCATE hep.hep_raw_30day";
$querydelete2 = $conn1->prepare($sqldelete2);
$querydelete2->execute();


//determine most recent sales date
//create 30 day table
$sql_30day = $conn1->prepare("INSERT into hep.hep_raw_30day (idsales, ITEM, PKGU, PKTYPE, UNITS, PICKDATE, LOCATION)
                                                                SELECT 
                                                                    idsales, ITEM, 1, PKTYPE, UNITS, PICKDATE, LOCATION
                                                                FROM
                                                                    (SELECT 
                                                                        A.*,
                                                                            @currcount:=IF(@currvalue = CONCAT(ITEM, PICKDATE), @currcount, IF(SUBSTRING(@currvalue, 1, 7) = ITEM, @currcount + 1, 1)) AS rank,
                                                                            @currvalue:=CONCAT(ITEM, PICKDATE) AS whatever
                                                                    FROM
                                                                        hep.hep_raw A
                                                                    ORDER BY ITEM , PICKDATE DESC) AS whatever
                                                                WHERE
                                                                    rank <= 61 AND UNITS > 0");
$sql_30day->execute();

//causing a memory issue on NY server.  Count items and find item 1/3 deep
$itemsql = $conn1->prepare("SELECT distinct
                                                            A.ITEM
                                                        FROM
                                                            hep.hep_raw_30day A
                                                                JOIN
                                                            hep.npfcpcsettings ON CPCITEM = ITEM
                                                                LEFT JOIN
                                                            hep.avg_inv V ON V.ITEM = A.ITEM
                                                        GROUP BY A.ITEM
                                                        ORDER BY A.ITEM ASC");
$itemsql->execute();
$itemarray = $itemsql->fetchAll(pdo::FETCH_COLUMN);

$itemcount = count($itemarray);

$onethird = intval($itemcount * .33);
$twothird = intval($itemcount * .67);

$onethird_item = $itemarray[$onethird];
$twohird_item = $itemarray[$twothird];
$loopcount = 1;

do {
    switch ($loopcount) {
        case 1:
            $limitsql = "WHERE A.ITEM <= $onethird_item ";
            break;
        case 2:
            $limitsql = "WHERE A.ITEM > $onethird_item and A.ITEM <= $twohird_item";
            break;
        case 3:
            $limitsql = "WHERE A.ITEM > $twohird_item ";
            break;
        default:
            break;
    }

//would only want to go back certain number of days
    $rawsql = $conn1->prepare("SELECT 
                                                        A.ITEM,
                                                        A.PKGU,
                                                        CASE WHEN A.PKTYPE in (101,111) then 'LSE' else 'CSE' end as PKTYPE,
                                                        A.PICKDATE,
                                                        COUNT(*) AS PICK_COUNT,
                                                        SUM(A.UNITS) AS UNITS_SUM,
                                                        CONCAT(A.ITEM, A.PKGU, A.PKTYPE) AS KEYVAL,
                                                        ceil(AVG(V.AVG_OH)) AS INV_OH
                                                    FROM
                                                        hep.hep_raw_30day A
                                                            JOIN
                                                        hep.npfcpcsettings ON CPCITEM = ITEM
                                                            LEFT JOIN
                                                        hep.avg_inv V ON V.ITEM = A.ITEM
                                                        $limitsql and CASE WHEN A.PKTYPE in (101,111) then 'LSE' else 'CSE' end = 'LSE'
                                                    GROUP BY A.ITEM , A.PKGU , CASE WHEN A.PKTYPE in (101,111) then 'LSE' else 'CSE' end , A.PICKDATE
                                                    ORDER BY A.ITEM ASC , A.PICKDATE DESC");
    $rawsql->execute();
    $groupedarray = $rawsql->fetchAll(pdo::FETCH_ASSOC);

//would only want to go back certain number of days
    $datesql = $conn1->prepare("SELECT 
                                                    MAX(PICKDATE) as MAXDATE
                                                FROM
                                                    hep.hep_raw");
    $datesql->execute();
    $datesqlarray = $datesql->fetchAll(pdo::FETCH_ASSOC);

    $maxdate = $datesqlarray[0]['MAXDATE'];


    $columns = 'GROUPED_ITEM, GROUPED_PKGU, GROUPED_PKTYPE, GROUPED_DATE, GROUPED_PICKS, GROUPED_UNITS, GROUPED_PREVSALE, GROUPED_DSLS, GROUPED_INVOH';

    $maxrange = 2000;
    $counter = 0;
    $rowcount = count($groupedarray);

    do {
        if ($maxrange > $rowcount) {  //prevent undefined offset
            $maxrange = $rowcount - 1;
        }

        $data = array();
        $values = array();
        while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table //sub loop through items by whse to pull in CPC settings by whse/item
            $ITEM = intval($groupedarray[$counter]['ITEM']);
            if ($previousitem <> $ITEM) {
                $itemchange = 1;
            }
            $PKGU = intval($groupedarray[$counter]['PKGU']);
            $PKTYPE = $groupedarray[$counter]['PKTYPE'];
            $PICKDATE = date('Y-m-d', strtotime($groupedarray[$counter]['PICKDATE']));
            $PICK_COUNT = intval($groupedarray[$counter]['PICK_COUNT']);
            $UNITS_SUM = intval($groupedarray[$counter]['UNITS_SUM']);
            $INV_OH = intval($groupedarray[$counter]['INV_OH']);
            $KEYVAL = ($groupedarray[$counter]['KEYVAL']);

            //when item changes, don't calc DSLS
            If ($itemchange == 1) {
                $previousdate = $maxdate;
                $DSLS = intval(getWorkingDays($PICKDATE, $maxdate, $holidays));
            } else if ($maxrange === $counter) {
                $previousdate = '0000-00-00';
                $DSLS = 0;
            } else if ($KEYVAL !== $groupedarray[$counter + 1]['KEYVAL']) {
                $previousdate = '0000-00-00';
                $DSLS = 0;
            } else {
                $previousdate = date('Y-m-d', strtotime($groupedarray[$counter + 1]['PICKDATE']));
                $DSLS = intval(getWorkingDays($previousdate, $PICKDATE, $holidays));
            }
            $data[] = "($ITEM, $PKGU, '$PKTYPE', '$PICKDATE', $PICK_COUNT, $UNITS_SUM, '$previousdate', $DSLS, $INV_OH)";
            $counter += 1;
            $previousitem = $ITEM;
            $itemchange = 0;
        }
        $values = implode(',', $data);

        if (empty($values)) {
            break;
        }
        $sql = "INSERT IGNORE INTO hep.hep_grouped ($columns) VALUES $values";
        $query = $conn1->prepare($sql);
        $query->execute();
        $maxrange += 2000;
    } while ($counter <= $rowcount); //end of item by whse loop
    $loopcount += 1;
} while ($loopcount < 4); //end of item 1/3 split loop
//Truncate NPTLSD file
$sqldelete = "TRUNCATE hep.nptsld";
$querydelete = $conn1->prepare($sqldelete);
$querydelete->execute();

//Create NPTSLD file from hep_grouped
$sql2 = "INSERT IGNORE INTO hep.nptsld
                         SELECT 
                                A.GROUPED_ITEM,
                                A.GROUPED_PKGU,
                                A.GROUPED_PKTYPE,
                                COUNT(*),
                                B.RECENTDSLS,
                                AVG(A.GROUPED_DSLS),
                                STDDEV(A.GROUPED_DSLS),
                                AVG(A.GROUPED_PICKS),
                                STDDEV(A.GROUPED_PICKS),
                                AVG(A.GROUPED_UNITS),
                                STDDEV(A.GROUPED_UNITS),
                                CEIL(AVG(A.GROUPED_INVOH)),
                                0,
                                0
                             FROM
                                hep.hep_grouped A
                                    INNER JOIN
                                (SELECT 
                                    GROUPED_ITEM,
                                        GROUPED_PKGU,
                                        GROUPED_PKTYPE,
                                        GROUPED_DSLS AS RECENTDSLS
                                FROM
                                    hep.hep_grouped 
                                GROUP BY GROUPED_ITEM , GROUPED_PKGU , GROUPED_PKTYPE) B ON A.GROUPED_ITEM = B.GROUPED_ITEM
                                    AND A.GROUPED_PKGU = B.GROUPED_PKGU
                                    AND A.GROUPED_PKTYPE = B.GROUPED_PKTYPE
                               WHERE A.GROUPED_DSLS <> 0 and A.GROUPED_PKTYPE = 'LSE'
                            GROUP BY A.GROUPED_ITEM , A.GROUPED_PKGU , A.GROUPED_PKTYPE";
$query2 = $conn1->prepare($sql2);
$query2->execute();

//need to join up to item_location table to show any items that aren't don't have a NPTSLD record
$sql3 = "INSERT IGNORE INTO hep.nptsld
                         SELECT 
                                loc_item,
                                1,
                                'LSE',
                                1,
                                999,
                                999,
                                1,
                                0,
                                1,
                                0,
                                1,
                                0,
                                0,
                                CASE
                                    WHEN AVG_OH IS NULL THEN 1
                                    ELSE AVG_OH
                                END
                            FROM
                                hep.item_location A
                                    LEFT JOIN
                                hep.nptsld B ON loc_item = B.ITEM
                                    LEFT JOIN
                                hep.avg_inv C ON C.ITEM = loc_item
                            WHERE
                                B.ITEM IS NULL";
$query3 = $conn1->prepare($sql3);
$query3->execute();

//Update avg pick and units in nptsld file
$sql4 = "UPDATE hep.nptsld dest,
                    (SELECT 
                    ITEM,
                        CASE
                                WHEN A.ADBS >= 365 THEN 0
                                WHEN A.DSLS >= 180 THEN 0
                                WHEN
                                    A.AVG_PICK > A.AVG_UNITS
                                THEN
                                    (A.AVG_UNITS / (CASE
                                        WHEN X.CPCCPKU > 0 THEN X.CPCCPKU
                                        ELSE 1
                                    END)) / A.ADBS
                                WHEN A.ADBS = 0 AND A.DSLS = 0 THEN A.AVG_PICK
                                WHEN A.ADBS = 0 THEN (A.AVG_PICK / A.DSLS)
                                ELSE (A.AVG_PICK / A.ADBS)
                            END AS AVG_DAILY_PICK,
                            CASE
                                WHEN A.ADBS >= 365 THEN 0
                                WHEN A.DSLS >= 180 THEN 0
                                WHEN A.AVG_PICK > A.AVG_UNITS THEN A.AVG_UNITS / A.ADBS
                                WHEN A.ADBS = 0 AND A.DSLS = 0 THEN A.AVG_UNITS
                                WHEN A.ADBS = 0 THEN (A.AVG_UNITS / A.DSLS)
                                ELSE (A.AVG_UNITS / A.ADBS)
                            END AS AVG_DAILY_UNIT
                    FROM
                        hep.nptsld A
                    LEFT JOIN hep.npfcpcsettings X ON ITEM = CPCITEM) src 
                SET 
                    dest.AVG_DAILY_PICK = src.AVG_DAILY_PICK,
                    dest.AVG_DAILY_UNIT = src.AVG_DAILY_UNIT
                WHERE dest.ITEM = src.ITEM";
$query4 = $conn1->prepare($sql4);
$query4->execute();



