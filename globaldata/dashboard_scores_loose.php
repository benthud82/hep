<?php

//get whse for user
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

//$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


//$bottom1perc = $conn1->prepare("SELECT 
//                                count(*) as bottom1perc
//                            FROM
//                                hep.slottingscore
//                                WHERE
//                                    SCORE_WHSE = $var_whse
//                                        and SCORE_ZONE in ('LSE' , 'INP')");
//$bottom1perc->execute();
//$bottom1percarray = $bottom1perc->fetchAll(pdo::FETCH_ASSOC);
//
//$bottom1perclimit = intval($bottom1percarray[0]['bottom1perc'] * .01);
//$bottom10perclimit = intval($bottom1percarray[0]['bottom1perc'] * .1);

$loosescore_100data = $conn1->prepare("SELECT 
                                avg(items.SCORE_TOTALSCORE) as loosescore_bottom100
                            FROM
                                (SELECT 
                                    B.SCORE_TOTALSCORE
                                from
                                    hep.slottingscore B
                                WHERE
                                    B.SCORE_ZONE in ('LSE' , 'INP')
                                ORDER BY B.SCORE_TOTALSCORE asc
                                LIMIT 100) items");
$loosescore_100data->execute();
$loosescore_100dataarray = $loosescore_100data->fetchAll(pdo::FETCH_ASSOC);

$loosescore_bottom100 = number_format($loosescore_100dataarray[0]['loosescore_bottom100'] * 100, 1).'%';

$loosescore_1000data = $conn1->prepare("SELECT 
                                avg(items.SCORE_TOTALSCORE) as loosescore_bottom1000
                            FROM
                                (SELECT 
                                    B.SCORE_TOTALSCORE
                                from
                                    hep.slottingscore B
                                WHERE
                                    B.SCORE_ZONE in ('LSE' , 'INP')
                                ORDER BY B.SCORE_TOTALSCORE asc
                                LIMIT 1000) items");
$loosescore_1000data->execute();
$loosescore_1000dataarray = $loosescore_1000data->fetchAll(pdo::FETCH_ASSOC);

$loosescore_bottom1000 = number_format($loosescore_1000dataarray[0]['loosescore_bottom1000'] * 100, 1).'%';
