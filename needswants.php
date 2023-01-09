<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Needs / Wants</title>
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
                    <div class="pull-left  col-sm-6 col-lg-3  col-xl-2" >
                        <label>Select Level:</label>
                        <select class="selectstyle" id="levelsel" name="levelsel" style="padding: 5px; margin-right: 10px;">
                            <option value="A">LEVEL - A</option>
                            <option value="B">LEVEL - B</option>
                            <option value="C">LEVEL - C</option>
                            
                        </select>

                    </div>
                    <div class="pull-left  col-sm-6 col-lg-3  col-xl-2" >
                        <label>Select Tier:</label>
                        <select class="selectstyle" id="tiersel" name="tiersel" style="width: 100px;padding: 5px; margin-right: 10px;">
                            <option value=0></option>
                            <option value="L01">L01</option>
                            <option value="L02">L02</option>
                            <option value="L04">L04</option>
                            <option value="L06">L06</option>
                        </select>

                    </div>
                    <div class="pull-left  col-lg-3 col-sm-6 col-xl-2" >
                        <label>Walk:</label>
                        <input name='baynum' class='selectstyle' id='baynum'/>
                    </div>

                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-lg-2" >
                            <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();" style="margin-bottom: 5px;">Load Data</button>
                        </div>
                    </div>
                </div>

                <div id="tablecontainer" class="hidden">
                    <table id="ptbtable" class="table table-bordered" cellspacing="0" style="font-size: 11px; font-family: Calibri;">
                        <thead>
                            <tr>
                                <th class="pers_tablehead">Grid5 - Depth</th>
                                <th class="pers_tablehead">Suggested Count</th>
                                <th class="pers_tablehead">Current Count</th>
                                <th class="pers_tablehead">Count + / -</th>
                                <th class="pers_tablehead">Cubic Meters + / -</th>
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
                var baynum = $('#baynum').val();
                var tiersel = $('#tiersel').val();
                var levelsel = $('#levelsel').val();

                oTable = $('#ptbtable').dataTable({
                    dom: "<'row'<'col-sm-4 pull-left'B><'col-sm-4 text-center'><'col-sm-4 pull-right'>>" + "<'row'<'col-sm-12't>>" + "<'row'<'col-sm-4 pull-left'><'col-sm-8 pull-right'>>",
                    destroy: true,
                    "scrollX": true,
                    "iDisplayLength": -1,
                    "aaSorting": [],
                    'sAjaxSource': "globaldata/needswantsdata.php?userid=" + userid + "&baynum=" + baynum + "&tiersel=" + tiersel + "&levelsel=" + levelsel,
                    buttons: [
                        'copyHtml5',
                        'excelHtml5'
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
