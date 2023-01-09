<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Receiving Analyzer</title>
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
                    <div class="pull-left  col-lg-2" >
                        <label>Enter Item: </label>
                        <input name='itemnum' class='selectstyle' id='itemnum'/>
                    </div>

                    <div class="col-md-3 col-xl-2" style="">
                        <div class="form-group">
                            <label>Date:</label>
                            <input name="startfiscal" id="startfiscal" class="selectstyle" style="cursor: pointer; max-width: 120px;"  value="<?php echo date("m/d/Y"); ?>"/>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2 text-center">
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();">Load Data</button>
                    </div>

                </div>
                
                <div id="tablecontainer"></div>

            </section>
        </section>


        <script>


            $('#startfiscal').datepicker();
            function gettable() {

                $('#tablecontainer').addClass('hidden');
                var itemnum = $('#itemnum').val();
                var datesel = $('#startfiscal').val();
                var userid = $('#userid').text();
                debugger;
                var formData = 'itemnum=' + itemnum + '&datesel=' + datesel + '&userid=' + userid;
                $.ajax({
                    url: 'globaldata/receivinganaldata.php',
                    type: 'POST',
                    data: formData,
                    success: function (result) {
                        $("#tablecontainer").html(result);
                    }
                });


                $('#tablecontainer').removeClass('hidden');

            }

        </script>

        <!--Personal Script for showing and completing item comments-->
        <script src="js/itemcomments.js" type="text/javascript"></script>


        <script>
            $("#reports").addClass('active');
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
        </script>
    </body>
</html>
