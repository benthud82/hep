<!DOCTYPE html>
<html>

    <head>
        <title>OSS - 2018 Slotting Goal</title>
        <?php
        include_once 'headerincludes.php';
        include 'sessioninclude.php';
        ?>
        <?php include_once 'connection/connection_details.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"style="padding-top: 75px;"> 


                <div class="row" style="padding-bottom: 30px;"> 
                    <div class="col-xs-12 ">
                        <button style="margin-left: 15px;"class="btn btn-primary pull-left" id="loaddata" type="button" onclick="gettable();">Re-Load Data</button>
                        <button style="margin-left: 15px;"class="btn btn-default pull-left"  id="helpbutton_goal" type="button"onclick="showhelpmodal_goal();" style="margin-bottom: 5px;"><i class="fa fa-question-circle"></i> Help</button>
                    </div>
                </div>

                <!--High level statitics-->
                <div id="headerstats"></div>


                <!--Bottom 1000 table.  -->
                <section class="panel portlet-item hidden" style="opacity: 1; z-index: 0; margin-top: 15px;" id="panel_itemlisting"> 
                    <header class="panel-heading bg-inverse"> 2018 Slotting Goal - Item Listing <i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_progresstracking"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                                <button id="btn_markedasmoved" type="button" class="btn btn-danger" onclick="">Mark Selections as Moved</button>
                            </div>
                        </div>
                        <div id="container_btm1000table" class="">
                            <table id="tbl_btm1000" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri; background-color: white ">
                                <thead>
                                    <tr>
                                        <th>Moved?</th>
                                        <th>Go To Move Assist</th>
                                        <th>Item</th>
                                        <th>Pkgu</th>
                                        <th>Location</th>
                                        <th>Curr. Tier</th>
                                        <th>Sugg. Tier</th>
                                        <th>Curr. Grid5</th>
                                        <th>Sugg. Grid5</th>
                                        <th>Sugg. Depth</th>
                                        <th>Sugg. Slot Qty</th>
                                        <th>Sugg. Max</th>
                                        <th>Curr. Max</th>
                                        <th>Sugg. Bay</th>
                                        <th>Curr. Bay</th>
                                        <th>Curr. Implied Moves</th>
                                        <th>Sugg. Implied Moves</th>
                                        <th>Avg Daily Pick</th>
                                        <th>Avg Daily Unit</th>
                                        <th>Total Score</th>
                                        <th>Opt Total Score</th>
                                        <th>Replen Score</th>
                                        <th>Opt Replen Score</th>
                                        <th>Walk Score</th>
                                        <th>Opt Walk Score</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </section>

                <!--Progress Chart for Tracking-->
                <div class="hidewrapper">
                    <section class="panel portlet-item hidden" style="opacity: 1; z-index: 0; margin-top: 15px;" id="panel_graphprogress"> 
                        <header class="panel-heading bg-inverse"> Progress Tracking <i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_progresstracking"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                        <div class="panel-body">
                            <div id="chartpage_progress"  class="page-break" style="width: 100%">
                                <div id="chart_progresstracking" class="hidden">
                                    <div id="ctn_progresstracking" class="largecustchartstyle printrotate"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!--Items moved table  -->
                <section class="panel portlet-item hidden" style="opacity: 1; z-index: 0; margin-top: 15px;" id="panel_itemsmoved"> 
                    <header class="panel-heading bg-inverse"> Items Moved Tracking <i class="fa fa-close pull-right closehidden" style="cursor: pointer;" id="close_itemsmoved"></i><i class="fa fa-chevron-up pull-right clicktotoggle-chevron" style="cursor: pointer;"></i></header> 
                    <div class="panel-body">

                        <div id="container_itemsmoved" class="">
                            <table id="tbl_itemsmoved" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri; background-color: white ">
                                <thead>
                                    <tr>
                                        <th>Show Item History</th>
                                        <th>Item</th>
                                        <th>Pkgu</th>
                                        <th>TSM</th>
                                        <th>Move Date</th>
                                        <th>Current Score</th>
                                        <th>Score at Move</th>
                                        <th>Score Inc/Dec</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </section>

                <!--Shows user if post to table was a success.-->
                <div id="postsuccess"></div>

                <!--Modal for Goal Explanation-->
                <div id="container_helpmodal_goal" class="modal fade " role="dialog">
                    <div class="modal-dialog modal-lg">
                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">2018 Slotting Goal</h4>
                            </div>
                            <div class="modal-body" id="" style="margin: 2px 25px 25px 25px;">
                                <h4 style="font-family: calibri">Slotting Goal FAQ'S</h4>
                                <ul style="font-family: calibri">
                                    <li>A total of 100 slotting moves must be executed each month.</li>
                                    <li>The 100 moves must come from the list of items below.</li>
                                    <li>If you perform more than 100 slotting moves, you may not count those for next month's moves.  There are no "roll-over" moves.</li>
                                    <li>To get credit for a slotting move, you must "mark" the item as moved in the below table.</li>
                                    <li>It is highly recommended to mark the items as moved immediately after the slotting move is completed.  The item will likely drop off the table after the night stream recalc.</li>
                                    <li>If you forget to mark an item as moved before it drops off the table, please let me know.</li>
                                </ul>
                                <h4 style="font-family: calibri">Header Stats FAQ'S</h4>
                                <ul style="font-family: calibri">
                                    <li>Total Moves this Month - A count of total slotting moves executed this month.  Only counts items that have been marked as moved in the Item Listing Table below.</li>
                                    <li>Monthly Score Increase/Decrease - Average of the score change for the items moved this <strong>MONTH</strong></li>
                                    <li>Yearly Score Increase/Decrease - Average of the score change for the items moved this <strong>YEAR</strong></li>
                                </ul>
                                <h4 style="font-family: calibri">Item Listing Table FAQ'S</h4>
                                <ul style="font-family: calibri">
                                    <li>The table will show the 150 items with the most opportunity to reduce walk time and replenishments.</li>
                                    <li>Total Score - The current score for the item based on current walk time and current replenishments.  Higher scores are better.</li>
                                    <li>Optimal Total Score - Best possible score for the item based on optimal slotting recommendations.  Note: 100% score is not always attainable due to space constraints.</li>
                                    <li>Items with highest difference between Total Score and Optimal Total Score will be populated in the Item Listing Table.</li>
                                </ul>
                                <h4 style="font-family: calibri">Progress Tracking Graph FAQ'S</h4>
                                <ul style="font-family: calibri">
                                    <li>Graph allows you to track historical slotting moves executed by month.</li>
                                    <li>There is no need to send an email after each month as we will use this graph to measure moves executed.</li>
                                </ul>
                                <h4 style="font-family: calibri">Items Moved Tracking Table FAQ'S</h4>
                                <ul style="font-family: calibri">
                                    <li>Table lists all items that have been marked as moved as part of the 2018 Slotting Goal.</li>
                                    <li>Current Score - Current Total Score for the item.</li>
                                    <li>Score at Move - Total Score at the time the slotting move was marked as moved.</li>
                                    <li>Score Inc/Dec - Difference between the Score at Move and Current Score.  Positive improvement is indicated by a score increase.</li>
                                    <li>If multiple items are showing a score decrease after the slotting move has been executed, please let me know.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>


            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#modules").addClass('active');

            //function to mark selected items as moved
            $(document).on("click", "#btn_markedasmoved", function (e) {
                var userid = $('#userid').text();
                var itemarray = [];
                var arraycount = 0;
                $('input.input_checkbox').each(function () {
                    if ($(this).is(':checked')) {
                        var itemnum = $.trim($(this).attr('id'));
                        itemarray[arraycount] = [itemnum];
                        arraycount += 1;
                    }
                });
                var itemcount = (itemarray.length);
                $.ajax({
                    url: 'formpost/markasmoved.php',
                    type: 'post',
                    data: {itemarray: itemarray, userid: userid, itemcount: itemcount},
                    success: function (ajaxresult) {
                        gettable();
                        $("#postsuccess").html(ajaxresult);
                    }
                });
            });


            function gettable() {
                $('#panel_itemlisting').addClass('hidden');
                $('#panel_graphprogress').addClass('hidden');
                $('#panel_itemsmoved').addClass('hidden');
                var userid = $('#userid').text();

                //Data pull to refresh header case data
                $.ajax({
                    url: 'globaldata/data_2018headerstats.php',
                    type: 'POST',
                    data: {userid: userid},
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $("#headerstats").html(ajaxresult);
                        $('#ajaxloadergif').addClass('hidden');
                        $('#headerstats').removeClass('hidden');
                        $(window).resize();
                    }
                });




                oTable = $('#tbl_btm1000').dataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    "scrollX": true,
                    "fnCreatedRow": function (nRow, aData, iDataIndex) {
                        $('td:eq(0)', nRow).append("<div class='text-center'><input  id='" + aData[2] + "' type='checkbox'  class='input_checkbox'/> </div>");
                        $('td:eq(1)', nRow).append("<div class='text-center'><i class='fa fa-external-link-square click_moveassist' style='cursor: pointer;     margin-right: 5px;' data-toggle='tooltip' data-title='Go to Move Assist Tool' data-placement='top' data-container='body' ></i> </div>");
                    },
//                    "rowCallback": function (row, data, index) {
//                        if (data[7] !== null) {
//                            $(row).addClass('recentcomment');
//                        }
//                        if (data[8] === 'SHOW COMMENTS') {  //add class to show comment so modal can be displayed
//                            $('td:eq(8)', row).addClass("showallcomments");
//                        }
//                    },
//                    "order": [[15, "asc"]],
                    "aoColumnDefs": [
                        {
                            "aTargets": [2], // Column to target
                            "mRender": function (data, type, full) {
                                // 'full' is the row's data object, and 'data' is this column's data
                                // e.g. 'full[0]' is the comic id, and 'data' is the comic title
                                return '<a href="itemquery.php?itemnum=' + full[2] + '&userid=' + userid + '" target="_blank">' + data + '</a>';
                            }
                        }
                    ],
                    'sAjaxSource': "globaldata/tbl_bottom1000.php?userid=" + userid,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5'
                    ]
                });
                $('#panel_itemlisting').removeClass('hidden');
                $('#panel_graphprogress').removeClass('hidden');
                $('#panel_itemsmoved').removeClass('hidden');
                highchartoptions();
                itemsmoved();

            }

            //Chart options and ajax for labor hours by hour
            function highchartoptions() {
                //Highchart variables for total hours not printed history
                var options = {
                    chart: {
                        marginTop: 50,
                        marginBottom: 130,
                        renderTo: 'ctn_progresstracking',
                        type: 'column',
                        zoomType: 'x',
                        height: 600
                    },
                    credits: {
                        enabled: false
                    },
                    plotOptions: {
                        spline: {
                            marker: {
                                enabled: false
                            }
                        }
                    },
                    title: {
                        text: ' '
                    },
                    xAxis: {
                        categories: [],
                        labels: {
                            rotation: -90,
                            y: 25,
                            align: 'right',
                            style: {
                                fontSize: '12px',
                                fontFamily: 'Verdana, sans-serif'
                            }
                        },
                        minTickInterval: 1,
                        legend: {
                            y: "10",
                            x: "5"
                        }

                    },
                    yAxis: {
                        opposite: true,
                        min: 0,
                        title: {
                            text: 'Slotting Moves Executed'
                        },
                        labels: {
                            formatter: function () {
                                return this.value;
                            }
                        }
                    },

                    tooltip: {
                        formatter: function () {
                            return '<b>' + this.series.name + ': </b>' + this.y;
                        }
                    },
                    series: []
                };
                $.ajax({
                    url: 'globaldata/graphdata_goalprogress2018.php',
                    type: 'GET',
                    dataType: 'json',
                    async: 'true',
                    success: function (json) {
                        options.xAxis.categories = json[0]['data'];
                        options.series[0] = json[1];

                        chart = new Highcharts.Chart(options);
                        series = chart.series;
                        $('#chart_progresstracking').removeClass('hidden');
                        $(window).resize();
                    }
                });
            }

            //Table tracking score impact of items moved
            function itemsmoved() {

                var userid = $('#userid').text();
                oTable2 = $('#tbl_itemsmoved').dataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    "scrollX": true,
                    "fnCreatedRow": function (nRow, aData, iDataIndex) {
                        $('td:eq(0)', nRow).append("<div class='text-center'><i class='fa fa-external-link-square gotoitemhistory' style='cursor: pointer;     margin-right: 5px;' data-toggle='tooltip' data-title='Go to Item History' data-placement='top' data-container='body' ></i></div>");
                    },
                    "order": [[4, "desc"]],
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
                    'sAjaxSource': "globaldata/tbl_goalitemsmoved.php?userid=" + userid,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5'
                    ]
                });
            }

            $(document).ready(function () {
                gettable();
            });

            function showhelpmodal_goal() {  //show help modal
                $('#container_helpmodal_goal').modal('toggle');
            }

            $(document).on("click", ".gotoitemhistory", function (e) {
                debugger;
                var userid = $('#userid').text();
                var itemnum = ($(this).closest('tr').find('td:eq(1)').text());
                window.open("itemhistory.php?itemnum=" + itemnum + "&userid=" + userid);
            });

            $(document).on("click", ".click_moveassist", function (e) {
                debugger;
                var userid = $('#userid').text();
                var itemnum = ($(this).closest('tr').find('td:eq(2)').text());
                window.open("moveassist.php?itemnum=" + itemnum + "&userid=" + userid);
            });
            
            
        </script>

    </body>
</html>


