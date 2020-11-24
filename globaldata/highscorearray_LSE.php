<?php

$whs6L01filter = " and A.LMTIER <> 'L01' and A.SUGGESTED_TIER <> 'L01'";


$TOP_SCORE = $conn1->prepare("SELECT DISTINCT
                                                        A.*,
                                                        B.OPT_NEWGRIDVOL,
                                                        B.OPT_PPCCALC,
                                                        B.OPT_OPTWALKFEET,
                                                        B.OPT_CURRWALKFEET,
                                                        B.OPT_CURRDAILYFT,
                                                        B.OPT_SHLDDAILYFT,
                                                        B.OPT_ADDTLFTPERPICK,
                                                        B.OPT_ADDTLFTPERDAY,
                                                        B.OPT_WALKCOST,
                                                        loc_truefit AS CURMAX,
                                                        loc_minqty AS CURMIN,
                                                        loc_truefit AS VCCTRF,
                                                        E.SCORE_TOTALSCORE,
                                                        E.SCORE_REPLENSCORE,
                                                        E.SCORE_WALKSCORE,
                                                        E.SCORE_TOTALSCORE_OPT,
                                                        E.SCORE_REPLENSCORE_OPT,
                                                        E.SCORE_WALKSCORE_OPT,
                                                        F.CPCPFRL
                                                    FROM
                                                        hep.my_npfmvc A
                                                            JOIN
                                                        hep.optimalbay B ON A.ITEM_NUMBER = B.OPT_ITEM
                                                        AND A.CUR_LEVEL = B.OPT_LEVEL
                                                            JOIN
                                                        hep.item_location ON A.ITEM_NUMBER = loc_item
                                                        AND A.CUR_LEVEL = loc_level
                                                            JOIN
                                                        hep.slottingscore E ON E.SCORE_ITEM = A.ITEM_NUMBER
                                                            JOIN
                                                        hep.npfcpcsettings F ON F.CPCITEM = A.ITEM_NUMBER
                                                            LEFT JOIN
                                                        hep.item_settings ON ITEM = ITEM_NUMBER
                                                            LEFT JOIN
                                                        hep.itemsmoved_2018goal ON goal_item = A.ITEM_NUMBER
                                                    WHERE
                                                        (HOLDLOCATION IS NULL
                                                            OR HOLDLOCATION = ' ')
                                                            AND goal_item IS NULL
                                                            $itemnumsql
                                                            and CUR_LEVEL like '$var_levelsel'
                                                    ORDER BY E.SCORE_TOTALSCORE_OPT - E.SCORE_TOTALSCORE DESC , E.SCORE_TOTALSCORE ASC , E.SCORE_REPLENSCORE ASC , E.SCORE_WALKSCORE ASC
                                                    LIMIT $sqlreturn ");
$TOP_SCORE->execute();
$TOP_REPLEN_COST_array = $TOP_SCORE->fetchAll(pdo::FETCH_ASSOC);

