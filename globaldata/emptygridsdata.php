<?php





$EMPTYGRID_result = $conn1->prepare("SELECT 
                                                                    DIMGROUP AS LMGRD5,
                                                                    TIER AS LMTIER,
                                                                    HIGH AS LMHIGH,
                                                                    DEEP AS LMDEEP,
                                                                    WIDE AS LMWIDE,
                                                                    CONCAT(LEVEL, TIER, DIMGROUP, DEEP, WALKFEET) AS EMPTYGRID,
                                                                    WALKFEET,
                                                                    VOLUME AS LMVOL9
                                                                FROM
                                                                    hep.bay_location
                                                                        LEFT JOIN
                                                                    hep.item_location ON LOCATION = loc_location
                                                                WHERE
                                                                    loc_item IS NULL
                                                                        AND WALKFEET = $OPT_OPTBAY
                                                                        AND TIER = '$tier'
                                                                    ORDER BY LMVOL9 DESC");
$EMPTYGRID_result->execute();
$EMPTYGRID_array = $EMPTYGRID_result->fetchAll(pdo::FETCH_ASSOC);
