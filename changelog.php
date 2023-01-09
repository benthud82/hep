<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    $var_userid = $_SESSION['MYUSER'];
    ?>
    <head>
        <title>Change Log</title>
        <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>
        <?php
        include_once 'connection/connection_details.php';
        include_once 'globaldata/openchangelogs.php';
        include_once 'globaldata/completedchangelogs.php';

        $compcount = count($completedlogsarray);
        $opencount = count($openlogsarray);
        ?>

        <section id="content"> 
            <section class="main padder"> 
                <div class="row" style="margin-top: 70px;">
                    <div class="col-md-6 col-lg-2" style="margin-bottom: 20px;">
                        <?php if ($var_userid === 'BHUD01') { ?>
                            <a style="cursor: pointer;" class="btn btn-round btn-inverse addtask">
                                Add Task
                            </a>
                        <?php } ?>
                    </div>
                    <div class="col-md-6 col-lg-2" style="margin-bottom: 20px;">
                        <?php if ($var_userid === 'BHUD01') { ?>
                            <a onclick="completetask();" style="cursor: pointer;" class="btn btn-round btn-inverse">
                                Complete Task
                            </a>
                        <?php } ?>
                    </div>

                </div>

                <div class="row">
                    <div class="col-sm-6 portlet ui-sortable">
                        <section class="panel portlet-item" style="opacity: 1;"> 
                            <header class="panel-heading bg-info" style="font-size: 20px"> Change Log Open Tasks <i class="fa fa-question-circle pull-right" data-toggle='tooltip' data-title='Open requests sorted by priority' data-placement='top' data-container='body' ></i></header> 
                            <section class="panel-body"> 

                                <?php foreach ($openlogsarray as $key => $value) { ?>
                                    <article class="media"> 
                                        <div class="pull-left thumb-small">
                                            <span class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x text-muted"></i><i class="fa-stack-1x text-white" style="font-family: calibri;"><?php echo $openlogsarray[$key]['changelog_priority']; ?></i>  </span> 
                                        </div> 
                                        <div class="media-body"> 
                                            <div class="pull-right media-mini text-center text-muted"> 
                                                <strong class="h4"><?php echo $openlogsarray[$key]['changelog_reqdate']; ?></strong><br> 
                                                <small class="label bg-light">Request Date</small> 
                                            </div> 
                                            <a href="#" class="h4"><?php echo $openlogsarray[$key]['changelog_description']; ?></a> 
                                            <small class="block"><?php echo $openlogsarray[$key]['changelog_comment']; ?></small> 
                                        </div> 
                                    </article> 
                                    <?php if ($key + 1 <> $opencount) { ?>
                                        <div class="line pull-in"></div> 
                                    <?php } ?>

                                <?php }
                                ?>

                            </section> 
                        </section>
                    </div>

                    <div class="col-sm-6 portlet ui-sortable">
                        <section class="panel portlet-item" style="opacity: 1;"> 
                            <header class="panel-heading bg-success" style="font-size: 20px"> Change Log Completed Tasks <i class="fa fa-question-circle pull-right" data-toggle='tooltip' data-title='Completed requests sorted by most recent completion date' data-placement='top' data-container='body' ></i></header> 
                            <section class="panel-body"> 

                                <?php foreach ($completedlogsarray as $key => $value) { ?>
                                    <article class="media"> 
                                        <div class="pull-left thumb-small">
                                            <span class="fa-stack fa-lg"> <i class="fa fa-circle fa-stack-2x text-success"></i> <i class="fa fa-check fa-stack-1x text-white"></i> </span>
                                        </div> 
                                        <div class="media-body"> 
                                            <div class="pull-right media-mini text-center text-muted"> 
                                                <strong class="h4"><?php echo $completedlogsarray[$key]['changelog_completedate']; ?></strong><br> 
                                                <small class="label bg-light">Completed Date</small> 
                                            </div> 
                                            <a href="#" class="h4"><?php echo $completedlogsarray[$key]['changelog_description']; ?></a> 
                                            <small class="block"><?php echo $completedlogsarray[$key]['changelog_comment']; ?></small> 
                                        </div> 
                                    </article> 
                                    <?php if ($key + 1 <> $compcount) { ?>
                                        <div class="line pull-in"></div> 
                                    <?php } ?>

                                <?php }
                                ?>

                            </section> 
                        </section>
                    </div>

                </div>
            </section>


            <!-- Add Comment Modal -->
            <div id="addtaskmodal" class="modal fade " role="dialog">
                <div class="modal-dialog modal-lg">

                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Add Change Log Task</h4>
                        </div>
                        <form class="form-horizontal" id="postitemaction">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label class="col-md-3 control-label">Requested TSM: </label>
                                    <div class="col-md-9">
                                        <input type="text" name="reqtsmnmodal" id="reqtsmnmodal" class="form-control" placeholder="Enter Requested TSM..." tabindex="1" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">Description: </label>
                                    <div class="col-md-9">
                                        <input type="text" name="descriptionmodal" id="descriptionmodal" class="form-control" placeholder="Enter High-level Description..." tabindex="3"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">Enter Detailed Comment: </label>
                                    <div class="col-md-9">
                                        <textarea rows="3" placeholder="" class="form-control" id="commentmodal" name="commentmodal" tabindex="7"></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">Enter Priority: </label>
                                    <div class="col-md-9">
                                        <input type="text" name="prioritymodal" id="prioritymodal" class="form-control" placeholder="Enter Priority..." tabindex="3"/>
                                    </div>  
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg pull-left" name="addchangelog" id="addchangelog">Add Change Log Task</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#changelog").addClass('active'); //add active strip to audit on vertical nav


            //show action modal
            $(document).on("click", ".addtask", function (e) {
                $('#addtaskmodal').modal('toggle');

            });



            //submit change log task from modal
            $(document).on("click", "#addchangelog", function (event) {
                event.preventDefault();
                var reqtsmnmodal = $('#reqtsmnmodal').val();
                var descriptionmodal = $('#descriptionmodal').val();
                var prioritymodal = $('#prioritymodal').val();
                var commentmodal = $('#commentmodal').val();
                debugger;
                var formData = 'reqtsmnmodal=' + reqtsmnmodal + '&descriptionmodal=' + descriptionmodal + '&commentmodal=' + commentmodal + '&prioritymodal=' + prioritymodal;
                $.ajax({
                    url: 'formpost/postaddchangelog.php',
                    type: 'POST',
                    data: formData,
                    success: function (result) {
                        $('#addtaskmodal').modal('hide');
                    }
                });
            });



        </script>

    </body>
</html>
