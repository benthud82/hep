<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    include_once '../globalfunctions/slottingfunctions.php';
    $today = date('m/d/Y');
    ?>
    <head>
        <title>OSS - Shorts Detail</title>
        <link href="../jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css"/>
        <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 
                <div class="row" style="padding-bottom: 25px; padding-top: 75px;"> 

                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>Start Date:</label>
                            <input name="startfiscal" id="startfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;" value="<?php echo date("m/d/Y", strtotime($today)); ?>"/>
                        </div>
                    </div>
                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>End Date:</label>
                            <input name="endfiscal" id="endfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;" value="<?php echo date("m/d/Y", strtotime($today)); ?>"/>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>
                </div>

                <div id="tablecontainer" class="hidden">
                    <table id="ptbtable" class="table table-striped table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                        <thead>
                            <tr>
                            <th>Date</th>
                            <th>Item Code</th>
                            <th>Location</th>
                            <th>Item MC</th>
                            <th>Batch Num</th>
                            <th>Qty Ordered</th>
                            <th>Qty Picked</th>
                            <th>Date/Time Picked</th>
                            <th>Date/Time Created</th>
                            </tr>
                        </thead>
                    </table>
                </div>

            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});


            function gettable(startdatefromurl, enddatefromurl) {
                $('#tablecontainer').addClass('hidden');

                if (typeof startdatefromurl !== 'undefined') {
                    var startdate = startdatefromurl;
                    var enddate = enddatefromurl;
                    var userid = $('#userid').text();
                } else {
                    var userid = $('#userid').text();
                    var startdate = $('#startfiscal').val();
                    var enddate = $('#endfiscal').val();
                }



                oTable = $('#ptbtable').dataTable({
                    dom: "<'row'<'col-sm-4 pull-left'l><'col-sm-4 text-center'B><'col-sm-4 pull-right'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-4 pull-left'i><'col-sm-8 pull-right'p>>",
                    destroy: true,
                    "scrollX": true,
                    "order": [[4, "asc"]],
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
                    'sAjaxSource': "globaldata/data_shortsdetail.php?userid=" + userid + "&startdate=" + startdate + "&enddate=" + enddate,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5',
                        'csvHtml5'
                    ]
                });
                $('#tablecontainer').removeClass('hidden');
                if (typeof startdatefromurl !== 'undefined') {
                    filldatevalue(startdatefromurl, enddatefromurl);  //file the item input field
                    cleanurl();  //clean the URL of post data
                }
            }
        </script>

        <script>
            $("#reports").addClass('active');
            $('#startfiscal').datepicker();
            $('#endfiscal').datepicker();

            //is date in url to auto load data
            $(document).ready(function () {
                if (window.location.href.indexOf("startdate") > -1) {
                    debugger;
                    //Place this in the document ready function to determine if there is search variables in the URL.  
                    //Must clean the URL after load to prevent looping
                    var startdatefromurl = GetUrlValue('startdate');
                    var enddatefromurl = GetUrlValue('enddate');

                    gettable(startdatefromurl, enddatefromurl);  //pass the 
                }
            });


            function GetUrlValue(VarSearch) {  //parse URL to pull variable defined
                debugger;
                var SearchString = window.location.search.substring(1);
                var VariableArray = SearchString.split('&');
                for (var i = 0; i < VariableArray.length; i++) {
                    var KeyValuePair = VariableArray[i].split('=');
                    if (KeyValuePair[0] === VarSearch) {
                        return KeyValuePair[1];
                    }
                }
            }

            function cleanurl() { //clean the URL if called from another page
                var clean_uri = location.protocol + "//" + location.host + location.pathname;
                window.history.replaceState({}, document.title, clean_uri);
            }

            function filldatevalue(startdatefromurl, enddatefromurl) {  //fill item input text
                debugger;
                document.getElementById("startfiscal").value = startdatefromurl;
                document.getElementById("endfiscal").value = enddatefromurl;
            }

        </script>
    </body>
</html>
