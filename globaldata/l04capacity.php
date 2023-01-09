<?php

//get whse for user
$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];


$lo4sql = $conn1->prepare("SELECT 
                                                            SUM(CASE
                                                                WHEN slotmaster_level = 'A' AND slotmaster_tier = 'L04' THEN slotmaster_usecube
                                                                ELSE 0
                                                            END) AS LEVELAVOL,
                                                            SUM(CASE
                                                                WHEN slotmaster_level = 'B' AND slotmaster_tier = 'L04' THEN slotmaster_usecube
                                                                ELSE 0
                                                            END) AS LEVELBVOL,
                                                            SUM(CASE
                                                                WHEN slotmaster_level = 'C' AND slotmaster_tier = 'L04' THEN slotmaster_usecube
                                                                ELSE 0
                                                            END) AS LEVELCVOL
                                                        FROM
                                                            hep.slotmaster;");
$lo4sql->execute();
$lo4sqlarray = $lo4sql->fetchAll(pdo::FETCH_ASSOC);

$availl04vol_A = ($lo4sqlarray[0]['LEVELAVOL']);
$availl04vol_B = ($lo4sqlarray[0]['LEVELBVOL']);
$availl04vol_C = ($lo4sqlarray[0]['LEVELCVOL']);

$lo4sql2 = $conn1->prepare("SELECT 
                                                                SUM(CASE
                                                                    WHEN
                                                                        CUR_LEVEL = 'A'
                                                                            AND SUGGESTED_TIER = 'L04'
                                                                    THEN
                                                                        SUGGESTED_NEWLOCVOL
                                                                    ELSE 0
                                                                END) AS USEDAL04,
                                                                SUM(CASE
                                                                    WHEN
                                                                        CUR_LEVEL = 'B'
                                                                            AND SUGGESTED_TIER = 'L04'
                                                                    THEN
                                                                        SUGGESTED_NEWLOCVOL
                                                                    ELSE 0
                                                                END) AS USEDBL04,
                                                                SUM(CASE
                                                                    WHEN
                                                                        CUR_LEVEL = 'C'
                                                                            AND SUGGESTED_TIER = 'L04'
                                                                    THEN
                                                                        SUGGESTED_NEWLOCVOL
                                                                    ELSE 0
                                                                END) AS USEDCL04
                                                            FROM
                                                                hep.my_npfmvc;");
$lo4sql2->execute();
$lo4sql2array = $lo4sql2->fetchAll(pdo::FETCH_ASSOC);

$USEDAL04 = ($lo4sql2array[0]['USEDAL04']);
$USEDBL04  = ($lo4sql2array[0]['USEDBL04']);
$USEDCL04  = ($lo4sql2array[0]['USEDCL04']);

$l04capacity_A = number_format($USEDAL04 / $availl04vol_A,4);
$l04capacity_B = number_format($USEDBL04 / $availl04vol_B,4);
$l04capacity_C = number_format($USEDCL04 / $availl04vol_C,4);
