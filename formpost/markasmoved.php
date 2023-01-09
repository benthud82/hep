
<?php
include_once '../connection/connection_details.php';
date_default_timezone_set('America/New_York');
$datetime = date('Y-m-d');
include_once '../sessioninclude.php';

ini_set('max_execution_time', 99999);
$var_userid = strtoupper($_SESSION['MYUSER']);
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE UPPER(idslottingDB_users_ID) = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$var_itemarray = ($_POST['itemarray']);

$values = array();
$pkgu = 1;

$maxrange = 4999;
$counter = 0;
$rowcount = count($var_itemarray);

$columns = "goal_whse, goal_item, goal_pkgu, goal_movedtsm, goal_movedate";

do {
    if ($maxrange > $rowcount) {  //prevent undefined offset
        $maxrange = $rowcount - 1;
    }

    $data = array();
    $values = array();
    while ($counter <= $maxrange) { //split into 10,000 lines segments to insert into merge table
        $itemcode = ($var_itemarray[$counter][0]);

        $data[] = "($var_whse, $itemcode, $pkgu, '$var_userid', '$datetime')";
        $counter += 1;
    }
    $values = implode(',', $data);

    if (empty($values)) {
        break;
    }
    $sql = "INSERT IGNORE INTO hep.itemsmoved_2018goal ($columns) VALUES $values";
    $query = $conn1->prepare($sql);
    $query->execute();
    $maxrange += 5000;
} while ($counter <= $rowcount);


$detailinsertsuccess = 1;
?>

<!-- Progress/Success Modal-->
<div id="progressmodal_salesplanall" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
            <div class="h4" style="text-align: center; padding-bottom: 30px">Successfully added <?php echo $rowcount . ' items to your total move count!'; ?> </div>
        </div>
    </div>
</div>
<script>
    $('#progressmodal_salesplanall').modal('toggle');
</script>