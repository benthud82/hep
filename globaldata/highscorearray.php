<?php

$TOP_SCORE = $conn1->prepare("SELECT 
                                        A . *,
                                        B.OPT_NEWGRIDVOL,
                                        B.OPT_PPCCALC,
                                        B.OPT_OPTBAY,
                                        B.OPT_CURRBAY,
                                        B.OPT_CURRDAILYFT,
                                        B.OPT_SHLDDAILYFT,
                                        B.OPT_ADDTLFTPERPICK,
                                        B.OPT_ADDTLFTPERDAY,
                                        B.OPT_WALKCOST,
                                        C.CURMAX,
                                        C.CURMIN,
                                        D.VCCTRF,
                                        E.SCORE_TOTALSCORE,
                                        E.SCORE_REPLENSCORE,
                                        E.SCORE_WALKSCORE,
                                        E.SCORE_TOTALSCORE_OPT,
                                        E.SCORE_REPLENSCORE_OPT,
                                        E.SCORE_WALKSCORE_OPT,
                                        F.CPCPFRC,
                                        F.CPCPFRA
                                    FROM
                                        hep.my_npfmvc A
                                            join
                                        hep.optimalbay B ON A.WAREHOUSE = B.OPT_WHSE
                                            and A.ITEM_NUMBER = B.OPT_ITEM
                                            and A.PACKAGE_UNIT = B.OPT_PKGU
                                            and A.PACKAGE_TYPE = B.OPT_CSLS
                                            join
                                        hep.mysql_npflsm C ON C.LMWHSE = A.WAREHOUSE
                                            and C.LMITEM = A.ITEM_NUMBER
                                            and C.LMTIER = A.LMTIER
                                            join
                                        hep.system_npfmvc D ON D.VCWHSE = A.WAREHOUSE
                                            and D.VCITEM = A.ITEM_NUMBER
                                            and D.VCPKGU = A.PACKAGE_UNIT
                                            and D.VCFTIR = A.LMTIER
                                            join
                                        hep.slottingscore E ON E.SCORE_WHSE = A.WAREHOUSE
                                            AND E.SCORE_ITEM = A.ITEM_NUMBER
                                            AND E.SCORE_PKGU = A.PACKAGE_UNIT
                                            AND E.SCORE_ZONE = A.PACKAGE_TYPE
                                            join
                                        hep.npfcpcsettings F ON F.CPCWHSE = A.WAREHOUSE
                                            and F.CPCITEM = A.ITEM_NUMBER
                                    WHERE
                                        A.WAREHOUSE = $var_whse
                                            and PACKAGE_TYPE = '$zone'
                                    ORDER BY E.SCORE_TOTALSCORE ASC, E.SCORE_REPLENSCORE, E.SCORE_WALKSCORE
                                    LIMIT $returncount");
$TOP_SCORE->execute();
$TOP_REPLEN_COST_array = $TOP_SCORE->fetchAll(pdo::FETCH_ASSOC);

