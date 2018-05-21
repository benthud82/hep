<?php
include_once '../connection/connection_details.php';


$tiersel = $_POST['tiersel'];

$grid5 = $conn1->prepare("SELECT 
                          SUGGESTED_GRID5 as SUGGESTED_GRID5data
                        FROM
                            hep.my_npfmvc
                        WHERE
                            SUGGESTED_TIER = '$tiersel'
                        GROUP BY   SUGGESTED_GRID5
                        ORDER BY SUGGESTED_GRID5");
$grid5->execute();
$grid5array = $grid5->fetchAll(pdo::FETCH_ASSOC);
?>


 <select class="selectstyle" id="grid5sel" name="grid5sel" style="width: 125px;padding: 5px; margin-right: 10px;">
    <option value="%">ALL</option>
    <?php foreach ($grid5array as $key => $value) {
      ?>  <option value="<?= $grid5array[$key]['SUGGESTED_GRID5data']; ?>"><?php echo $grid5array[$key]['SUGGESTED_GRID5data'];?></option>
   <?php } ?>

 </select>
