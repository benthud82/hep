<?php

if(!isset($addtlfilter)){
    $addtlfilter = '';
}

$EMPTYLOC_result = $conn1->prepare("SELECT 
                                                                                CONCAT(slotmaster_level,
                                                                                        slotmaster_tier,
                                                                                        slotmaster_dimgroup,
                                                                                        slotmaster_grdeep,
                                                                                        ROUND(slotmaster_distance)) AS KEYVAL,
                                                                                slotmaster_loc,
                                                                                slotmaster_dimgroup,
                                                                                slotmaster_locdesc,
                                                                                slotmaster_usehigh,
                                                                                slotmaster_usedeep,
                                                                                slotmaster_usewide,
                                                                                slotmaster_usecube,
                                                                                slotmaster_distance,
                                                                                slotmaster_bay,
                                                                                slotmaster_walkbay,
                                                                                slotmaster_level,
                                                                                slotmaster_fixt,
                                                                                slotmaster_fixt_desc,
                                                                                slotmaster_stor,
                                                                                slotmaster_stor_desc,
                                                                                slotmaster_tier
                                                                            FROM
                                                                                hep.slotmaster
                                                                                    LEFT JOIN
                                                                                item_location ON loc_location = slotmaster_loc
                                                                            WHERE
                                                                                loc_location IS NULL;");  
$EMPTYLOC_result->execute();
$EMPTYLOC_array = $EMPTYLOC_result->fetchAll(pdo::FETCH_ASSOC);