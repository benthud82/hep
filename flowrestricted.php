<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Flow Rack Restrictions</title>
        <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder" style="padding-top: 75px;"> 
                <div class="row" style="padding-bottom: 30px;"> 
                    <div class="pull-left  col-lg-3" >
                        <div class="col-md-12">
                            <label class="control-label" style=" float: left;">Include Already Reviewed?:</label>
                            <div class="switch-field"style="padding-left: 20px;">
                                <input type="radio" id="switch_left" name="switch_2" value="yes" />
                                <label for="switch_left" class="greenbackground" data-toggle="tooltip" data-title="Include IP opps already reviewed?" data-placement="bottom">Yes</label>
                                <input type="radio" id="switch_right" name="switch_2" value="no" checked />
                                <label id="nolabel" for="switch_right" data-toggle="tooltip" data-title="Include IP opps already reviewed?" data-placement="bottom">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>
                     <div class="col-md-4"><button id="helpbutton" type="button" class="btn btn-default" onclick="showhelpmodal();" style="margin-bottom: 5px;"><i class="fa fa-question-circle"></i> Help</button></div>
                </div>

                <div id="mastercontainer" class=" hidden"style="width: 1100px;" >
                    <h2>Flow Rack Restrictions</h2>

                    <div id="tablecontainer" class="">
                        <table id="ptbtable" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                            <thead>
                                <tr>
                                    <th>Mark as OK</th>
                                    <th>Item</th>
                                    <th>Location</th>
                                    <th>Current Grid5</th>
                                    <th>Suggested Slot Qty</th>
                                    <th>Suggested Max</th>
                                    <th>Reviewed?</th>
                                    <th data-toggle='tooltip' title='Click "SHOW COMMENTS" to view Comments' data-placement='top' data-container='body'>Comments?</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>



                <!--Modal for hierarchy explanation-->
                <div id="container_helpmodal" class="modal fade " role="dialog">
                    <div class="modal-dialog modal-lg">
                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Flow Rack Restriction Help</h4>
                            </div>
                            <div class="modal-body" id="" style="margin: 25px;">
                                <h4 style="font-family: calibri">This list identifies items that desire to go to the flow rack but are being restricted</h4>

                                <ul style="font-family: calibri">
                                    <li>The OK in Flow setting is set to "N".</li>
                                    <li>Based on cubic pick velocity, this item wants to go to the flow rack.</li>
                                    <li>The suggested slot quantity is the optimal quantity to store in the primary picking location.</li>
                                    <li>The suggested max is the true fit of the location the logic is suggesting.</li>
                                    <li>The difference between these two columns represents the opportunity cost of restricting the item from the flow rack.</li>
                                    <li>Potential to have increased replenishments because of the restriction.</li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>



            </section>
        </section>



        <!--Add comment modal-->
        <?php include_once 'globaldata/addcommentmodal.php'; ?>


        <!-- Mark as Reviewed Modal -->
        <div id="reviewmodal" class="modal fade " role="dialog">
            <div class="modal-dialog modal-lg">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Mark as Reviewed</h4>
                    </div>
                    <form class="form-horizontal" id="postreview">
                        <div class="modal-body">
                            <div class="form-group hidden">
                                <input type="text" name="itemnummodal" id="itemnummodal" class="form-control" tabindex="1" />
                            </div>
                            <div class="form-group hidden">
                                <input type="text" name="locationmodal" id="locationmodal" class="form-control" tabindex="1" />
                            </div>
                            <div class="form-group">
                                <label class="col-md-3 control-label">Mark item as reviewed: </label>
                                <div class="col-md-9">
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg pull-left" name="addreview" id="addreview">Yes!</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--Modal to view all comments for item-->
        <div id="allcommentsmodal" class="modal fade " role="dialog">
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Item Comments</h4>
                    </div>

                    <div class="modal-body" id="" style="margin: 50px;">
                        <div id="commentdetaildata"></div>
                    </div>

                </div>
            </div>
        </div>


        <!--Personal Script for showing and completing item comments-->
        <script src="js/itemcomments.js" type="text/javascript"></script>


        <script>
                            $("body").tooltip({selector: '[data-toggle="tooltip"]'});


                            function showhelpmodal() {  //show help modal
                                $('#container_helpmodal').modal('toggle');
                            }

                            //Toggle review modal
                            $(document).on("click", ".reviewclick", function (e) {
                                $('#reviewmodal').modal('toggle');
                                $('#itemnummodal').val($(this).closest('tr').find('td:eq(1)').text());
                            });

                            function gettable() {
                                debugger;
                                $('#mastercontainer').addClass('hidden');
                                var userid = $('#userid').text();
                                if (document.getElementById('switch_left').checked) {
                                    var includeaudit = 1;
                                } else {
                                    var includeaudit = 0;
                                }


                                oTable = $('#ptbtable').dataTable({
                                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                                    destroy: true,
                                    "scrollX": true,
                                    "fnCreatedRow": function (nRow, aData, iDataIndex) {
                                        $('td:eq(0)', nRow).append("<div class='text-center'><i class='fa fa-check reviewclick' style='cursor: pointer;     margin-right: 5px;' data-toggle='tooltip' data-title='Mark as reviewed?' data-placement='top' data-container='body' ></i><i id='" + aData[1] + "' class='fa fa-comment addcomment' style='cursor: pointer;' data-toggle='tooltip' data-title='Add Comment' data-placement='top' data-container='body' ></i> </div>");
                                    },
                                    "rowCallback": function (row, data, index) {
                                        if (data[6] !== null) {
                                            $(row).addClass('recentcomment');
                                        }
                                        if (data[7] === 'SHOW COMMENTS') {  //add class to show comment so modal can be displayed
                                            $('td:eq(7)', row).addClass("showallcomments");
                                        }
                                    },
                                    "order": [[1, "asc"]],
                                    "aoColumnDefs": [
                                        {
                                            "aTargets": [1], // Column to target
                                            "mRender": function (data, type, full) {
                                                // 'full' is the row's data object, and 'data' is this column's data
                                                // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                                                return '<a href="itemquery.php?itemnum=' + full[1] + '&userid=' + userid + '" target="_blank">' + data + '</a>';
                                            }
                                        }
                                    ],
                                    'sAjaxSource': "globaldata/flowrestrictdata.php?userid=" + userid + '&includeaudit=' + includeaudit,
                                    buttons: [
                                        'copyHtml5',
                                        'excelHtml5',
                                        'csvHtml5'
                                    ]
                                });
                                $('#mastercontainer').removeClass('hidden');

                            }

                            //mark item as reviewed through mysql post
                            $(document).on("click", "#addreview", function (event) {
                                event.preventDefault();
                                var itemnum = $('#itemnummodal').val();
                                var caseorip = 'FLOWREST';
                                var userid = $('#userid').text();
                                debugger;
                                var formData = 'itemnum=' + itemnum + '&userid=' + userid + '&caseorip=' + caseorip;
                                $.ajax({
                                    url: 'formpost/postmarkreviewed_caseip.php',
                                    type: 'POST',
                                    data: formData,
                                    success: function (result) {
                                        $('#reviewmodal').modal('hide');
                                        gettable();
                                    }
                                });
                            });


                            //if all comments for an item is wanted to be viewed through modal
                            $(document).on("click", ".showallcomments", function (event) {
                                $('#allcommentsmodal').modal('toggle');
                                $('#commentmodal_container').addClass('hidden');
                                var itemnum = $(this).closest('tr').find('td:eq(1)').text();
                                var userid = $('#userid').text();
                                $.ajax({
                                    url: 'globaldata/commentsbyitem.php',
                                    data: {itemnum: itemnum, userid: userid},
                                    type: 'POST',
                                    dataType: 'html',
                                    success: function (ajaxresult) {
                                        $('#commentmodal_container').removeClass('hidden');
                                        $("#commentdetaildata").html(ajaxresult);
                                    }
                                });

                            });

        </script>

        <script>
            $("#reports").addClass('active');

        </script>
    </body>
</html>
