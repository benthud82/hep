<?php

include_once '../connection/connection_details.php';


$table = 'hep.shortsdetail';

$startdate = date('Y-m-d', strtotime($_GET['startdate']));
$enddate = date('Y-m-d', strtotime($_GET['enddate']));

$shortdata = $conn1->prepare("SELECT * FROM {$table} WHERE ShortDate between '$startdate' and '$enddate' ");
$shortdata->execute();
$shortdata_array = $shortdata->fetchAll(pdo::FETCH_ASSOC);



$output = array(
    "aaData" => array()
);
$row = array();

foreach ($shortdata_array as $key => $value) {
    $row[] = array_values($shortdata_array[$key]);
}


$output['aaData'] = $row;
echo json_encode($output);
