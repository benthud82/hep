<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Items on Hold</title>
        <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 
                <h2 style="padding-bottom: 0px;padding-top: 75px;"></h2>

                <section class="panel hidewrapper" id="tbl_historicalscores" style="margin-bottom: 50px; margin-top: 20px;"> 
                    <header class="panel-heading bg bg-inverse h2">Table - Items on Hold<i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_tblitemsonhold"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header>
                    <div id="tbl_itemsonhold" class="panel-body">
                        <div id="tablecontainer" class="">
                            <table id="ptbtable" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Pkgu</th>
                                        <th>Hold Location</th>
                                        <th>Hold Grid5</th>
                                        <th>Hold Tier</th>
                                        <th data-toggle='tooltip' title='Click "SHOW COMMENTS" to view Comments' data-placement='top' data-container='body'>Comments?</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </section>


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


            </section>
        </section>

        <!--Personal Script for showing and completing item comments-->
        <script src="js/itemcomments.js" type="text/javascript"></script>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});


            $(document).ready(function () {

                var userid = $('#userid').text();
                //Item score history table
                oTable = $('#ptbtable').dataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    "scrollX": true,
                    "rowCallback": function (row, data, index) {
                        if (data[5] === 'SHOW COMMENTS') {  //add class to show comment so modal can be displayed
                            $('td:eq(5)', row).addClass("showallcomments");
                        }
                    },
                    "aoColumnDefs": [
                        {
                            "aTargets": [0], // Column to target
                            "mRender": function (data, type, full) {
                                // 'full' is the row's data object, and 'data' is this column's data
                                // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                                return '<a href="itemquery.php?itemnum=' + full[0] + '&userid=' + userid + '" target="_blank">' + data + '</a>';
                            }
                        }
                    ],
                    'sAjaxSource': "globaldata/dt_itemsonhold.php?userid=" + userid,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5'
                    ]
                });
            });


            //if all comments for an item is wanted to be viewed through modal
            $(document).on("click", ".showallcomments", function (event) {
                $('#allcommentsmodal').modal('toggle');
                $('#commentmodal_container').addClass('hidden');
                var itemnum = $(this).closest('tr').find('td:eq(0)').text();
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
