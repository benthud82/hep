<?php
include_once '../connection/connection_details.php';
$var_userid = $_POST['userid'];
$whssql = $conn1->prepare("SELECT slottingDB_users_PRIMDC from hep.slottingdb_users WHERE idslottingDB_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

$var_whse = $whssqlarray[0]['slottingDB_users_PRIMDC'];
$tiersel = $_POST['tiersel'];

//find group
//$mygroup = $conn1->prepare("SELECT customeraudit_users_GROUP
//                            FROM hep.customeraudit_users
//                            WHERE customeraudit_users_ID = '$var_userid'");
//$mygroup->execute();
//$mygrouparray = $mygroup->fetchAll(pdo::FETCH_ASSOC);
//$mygroupdata = $mygrouparray[0]['customeraudit_users_GROUP'];


$grid5_current = $conn1->prepare("SELECT 
                            concat(LMGRD5,
                                    ' Dep: ',
                                    LMDEEP) as SUGGESTED_GRID5data,
                                    LMGRD5
                        FROM
                            hep.my_npfmvc
                        WHERE
                            WAREHOUSE = $var_whse
                                and SUGGESTED_TIER = '$tiersel'
                        GROUP BY concat(SUGGESTED_GRID5,
                                ' Depth: ',
                                SUGGESTED_DEPTH)
                        ORDER BY substring(SUGGESTED_GRID5, 1, 2) * substring(SUGGESTED_GRID5, 4, 2) * SUGGESTED_DEPTH");
$grid5_current->execute();
$grid5_currentarray = $grid5_current->fetchAll(pdo::FETCH_ASSOC);
?>


 <select class="selectstyle" id="grid5sel_current" name="grid5sel_current" style="width: 120px;padding: 5px; margin-right: 10px;">
    <option value="&">ALL</option>
    <?php foreach ($grid5_currentarray as $key => $value) {
      ?>  <option value="<?= $grid5_currentarray[$key]['LMGRD5']; ?>"><?php echo $grid5_currentarray[$key]['SUGGESTED_GRID5data'];?></option>
   <?php } ?>

 </select>
