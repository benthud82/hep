
<?php
include_once '../connection/connection_details.php';
$var_item = $_POST['itemnum'];
$multprimsql = $conn1->prepare("SELECT 
                                                                    COUNT(*) as primcount
                                                                FROM
                                                                    hep.item_location
                                                                WHERE
                                                                    loc_item = $var_item");
$multprimsql->execute();
$multprimarray = $multprimsql->fetchAll(pdo::FETCH_ASSOC);

$primcount = $multprimarray[0]['primcount'];

if ($primcount > 1) {
    ?>
    <div class="col-md-6">
        <div class="alert alert-danger " style="font-size: 100%;"> <button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button> <i class="fa fa-info-circle fa-lg"></i><span> There are multiple primaries for this item.  Only one location is used for comparison.</span></div>
    </div>
<?php }



