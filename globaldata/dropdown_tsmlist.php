<?php
if (!isset($conn1)){
    include_once 'connection/connection_details.php';
}


$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];

$tsm = $conn1->prepare("SELECT 
                                                    idslottingDB_users_ID,
                                                    CONCAT(idslottingDB_users_ID,
                                                            ' | ',
                                                            slottingDB_users_FIRSTNAME,
                                                            ' ',
                                                            slottingDB_users_LASTNAME) AS FULLNAME
                                                FROM
                                                    hep.slottingdb_users ");
$tsm->execute();
$tsmlistarray = $tsm->fetchAll(pdo::FETCH_ASSOC);
?>


<select class="form-control" id="tsmlist" name="tsmlist" >
    <option value="0"></option>
    <?php foreach ($tsmlistarray as $key => $value) {
      ?>  <option value="<?= $tsmlistarray[$key]['idslottingDB_users_ID']; ?>"><?php echo $tsmlistarray[$key]['FULLNAME'];?></option>
   <?php } ?>

 </select>

