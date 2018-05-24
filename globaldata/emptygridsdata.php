<?php





$EMPTYGRID_result = $conn1->prepare("SELECT 
                                                                    slotmaster_dimgroup AS LMGRD5,
                                                                    slotmaster_tier AS LMTIER,
                                                                    slotmaster_usehigh AS LMHIGH,
                                                                    slotmaster_usedeep AS LMDEEP,
                                                                    slotmaster_usewide AS LMWIDE,
                                                                    CONCAT(slotmaster_level, slotmaster_tier, slotmaster_dimgroup, slotmaster_dimgroup, slotmaster_distance) AS EMPTYGRID,
                                                                    slotmaster_distance,
                                                                    slotmaster_usecube AS LMVOL9
                                                                FROM
                                                                    hep.slotmaster
                                                                        LEFT JOIN
                                                                    hep.item_location ON slotmaster_level = loc_location
                                                                WHERE
                                                                    loc_item IS NULL
                                                                        AND slotmaster_distance = $OPT_OPTBAY
                                                                        AND slotmaster_tier = '$tier'
                                                                    ORDER BY slotmaster_usecube DESC");
$EMPTYGRID_result->execute();
$EMPTYGRID_array = $EMPTYGRID_result->fetchAll(pdo::FETCH_ASSOC);
