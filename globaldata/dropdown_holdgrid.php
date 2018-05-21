<?php
include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];
$tiersel = $_POST['tiersel'];

$grid5 = $conn1->prepare("SELECT 
                           concat(SUGGESTED_GRID5, ' Depth: ',  SUGGESTED_DEPTH) as SUGGESTED_GRID5data,
                                    SUGGESTED_GRID5, substring(SUGGESTED_GRID5, 1, 2) * substring(SUGGESTED_GRID5, 4, 2) * SUGGESTED_DEPTH as VOLUME, count(*)
                        FROM
                            hep.my_npfmvc
                        WHERE
                            WAREHOUSE = $var_whse
                                and SUGGESTED_TIER = '$tiersel'
                        GROUP BY concat(SUGGESTED_GRID5, ' Depth: ',  SUGGESTED_DEPTH) ,
                                    SUGGESTED_GRID5, SUGGESTED_GRID5, substring(SUGGESTED_GRID5, 1, 2) * substring(SUGGESTED_GRID5, 4, 2) * SUGGESTED_DEPTH
                        ORDER BY substring(SUGGESTED_GRID5, 1, 2) * substring(SUGGESTED_GRID5, 4, 2) * SUGGESTED_DEPTH");
$grid5->execute();
$grid5array = $grid5->fetchAll(pdo::FETCH_ASSOC);
?>


<select class="form-control" id="grid5sel" name="grid5sel"  onchange="buttonstatuscheck();">
    <option value="0"></option>
    <?php foreach ($grid5array as $key => $value) {
      ?>  <option value="<?= $grid5array[$key]['SUGGESTED_GRID5']; ?>"><?php echo $grid5array[$key]['SUGGESTED_GRID5data'];?></option>
   <?php } ?>

 </select>
