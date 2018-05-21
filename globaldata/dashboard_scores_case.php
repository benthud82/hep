<?php

//get whse for user
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

//determine if Sparks building one or two
if ($var_whse == 32) {
    $sparksbuild2filter = " >= 'W300000'";
    $var_whse = 3;
} elseif ($var_whse == 3) {
    $sparksbuild2filter = " <= 'W299999'";
} else {
    $sparksbuild2filter = " >= ' '";
}



$casescore_100data = $conn1->prepare("SELECT 
                                        avg(items.SCORE_TOTALSCORE) as casescore_bottom100
                                    FROM
                                        (SELECT 
                                            B.SCORE_TOTALSCORE, B.SCORE_WHSE, B.SCORE_ITEM, B.SCORE_PKGU, B.SCORE_ZONE
                                        from
                                            hep.slottingscore B
                                            join
                                        hep.my_npfmvc C ON C.WAREHOUSE = B.SCORE_WHSE
                                            and C.ITEM_NUMBER = B.SCORE_ITEM
                                            and C.PACKAGE_UNIT = B.SCORE_PKGU
                                            and C.PACKAGE_TYPE = B.SCORE_ZONE
                                        WHERE
                                            B.SCORE_WHSE = $var_whse
                                                and B.SCORE_ZONE in ('CSE' , 'PFR')
                                        ORDER BY B.SCORE_TOTALSCORE asc
                                        LIMIT 100) items");
$casescore_100data->execute();
$casescore_100dataarray = $casescore_100data->fetchAll(pdo::FETCH_ASSOC);

$casescore_bottom100 = number_format($casescore_100dataarray[0]['casescore_bottom100'] * 100, 1).'%';

$casescore_1000data = $conn1->prepare("SELECT 
                                        avg(items.SCORE_TOTALSCORE) as casescore_bottom1000
                                    FROM
                                        (SELECT 
                                            B.SCORE_TOTALSCORE, B.SCORE_WHSE, B.SCORE_ITEM, B.SCORE_PKGU, B.SCORE_ZONE
                                        from
                                            hep.slottingscore B
                                            join
                                        hep.my_npfmvc C ON C.WAREHOUSE = B.SCORE_WHSE
                                            and C.ITEM_NUMBER = B.SCORE_ITEM
                                            and C.PACKAGE_UNIT = B.SCORE_PKGU
                                            and C.PACKAGE_TYPE = B.SCORE_ZONE
                                        WHERE
                                            B.SCORE_WHSE = $var_whse
                                                and B.SCORE_ZONE in ('CSE' , 'PFR')
                                        ORDER BY B.SCORE_TOTALSCORE asc
                                        LIMIT 1000) items");
$casescore_1000data->execute();
$casescore_1000dataarray = $casescore_1000data->fetchAll(pdo::FETCH_ASSOC);

$casescore_bottom1000 = number_format($casescore_1000dataarray[0]['casescore_bottom1000'] * 100, 1).'%';
