<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
        include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Case High to Low</title>
        <?php include_once 'headerincludes.php'; ?>
        <style>table.dataTable thead>tr>th.sorting_asc, table.dataTable thead>tr>th.sorting_desc, table.dataTable thead>tr>th.sorting, table.dataTable thead>tr>td.sorting_asc, table.dataTable thead>tr>td.sorting_desc, table.dataTable thead>tr>td.sorting {
                padding-right: 20px; padding-left: 2px; padding-top: 2px; padding-bottom: 2px}
            .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {padding: 2px; }
            td>a{text-decoration: underline;}
        </style>

    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 
                <div class="row" style="padding-bottom: 25px; padding-top: 75px;"> 

                    <div class="pull-left  col-lg-2" >
                        <label>Move item to:</label>
                        <select class="selectstyle" id="reportsel" name="reportsel" style="width: 120px;padding: 5px; margin-right: 10px;">
                            <option value=1>Main Building</option>
                            <option value=2>Case Building</option>
                        </select>
                    </div>
                                        <div class="col-lg-2" >
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();" style="margin-bottom: 5px;">Load Data</button>
                    </div>
                </div>





                <div id="tablecontainer" class="hidden">
                    <table id="ptbtable" class="table table-striped table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                        <thead>
                            <tr>
                                <th>Whse</th>
                                <th>Item</th>
                                <th>Location</th>
                                <th>DSLS</th>
                                <th>ADBS</th>
                                <th>Current Grid5</th>
                                <th>Current Depth</th>
                                <th>Current Bay</th>
                                <th>Suggested Grid5</th>
                                <th>Suggested Depth</th>
                                <th>Suggested Bay</th>
                                <th>Suggested Max</th>
                                <th>Suggested Min</th>
                                <th>Implied Replen</th>
                                <th>Currrent Replen</th>
                                <th>Daily Pick</th>
                                <th>Daily Unit</th>
                                <th>Replen Score</th>
                                <th>Walk Score</th>
                                <th>Total Score</th>
                            </tr>
                        </thead>
                    </table>
                </div>



            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});


            function gettable() {

                $('#tablecontainer').addClass('hidden');
                var userid = $('#userid').text();
                var reportsel = $('#reportsel').val();

                oTable = $('#ptbtable').dataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    "scrollX": true,
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
                    'sAjaxSource': "globaldata/buildingswapdata.php?userid=" + userid + "&reportsel=" + reportsel,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5'
                    ]
                });
                $('#tablecontainer').removeClass('hidden');

            }
        </script>

        <script>
            $("#reports").addClass('active');

        </script>
    </body>
</html>
