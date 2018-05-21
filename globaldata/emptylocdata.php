<?php

if(!isset($addtlfilter)){
    $addtlfilter = '';
}

$EMPTYLOC_result = $conn1->prepare("SELECT 
                                                                                CONCAT(LEVEL,
                                                                                        TIER,
                                                                                        DIMGROUP,
                                                                                        DEEP,
                                                                                        ROUND(WALKFEET)) AS KEYVAL,
                                                                                LOCATION,
                                                                                DIMGROUP,
                                                                                LOCDESC,
                                                                                HIGH,
                                                                                DEEP,
                                                                                WIDE,
                                                                                VOLUME,
                                                                                WALKFEET,
                                                                                BAY,
                                                                                WALKBAY,
                                                                                LEVEL,
                                                                                FIXT,
                                                                                FIXTDESC,
                                                                                STGT,
                                                                                STGTDESC,
                                                                                TIER
                                                                            FROM
                                                                                hep.bay_location
                                                                                    LEFT JOIN
                                                                                item_location ON loc_location = LOCATION
                                                                            WHERE
                                                                                loc_location IS NULL;");  
$EMPTYLOC_result->execute();
$EMPTYLOC_array = $EMPTYLOC_result->fetchAll(pdo::FETCH_ASSOC);