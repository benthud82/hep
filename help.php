<!DOCTYPE html>
<html>

    <head>
        <title>Help</title>
        <?php
        // include_once 'headerincludes.php'; 
        include 'sessioninclude.php';
        ?>
        <meta charset="utf-8">
        <link rel="shortcut icon" type="image/ico" href="../favicon.ico" />  
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
        <link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
        <script src="../DataTables/datatables.js" type="text/javascript"></script>-->
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="shortcut icon" type="image/ico" href="../favicon.ico" />  
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
        <link href="osscss/offsys_dash.css" rel="stylesheet" type="text/css"/>
        <link href="osscss/offsys_personal.css" rel="stylesheet" type="text/css"/>
        <style type="text/css">.jqstooltip { position: absolute;left: 0px;top: 0px;visibility: hidden;background: rgb(0, 0, 0) transparent;background-color: rgba(0,0,0,0.6);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";color: white;font: 10px arial, san serif;text-align: left;white-space: nowrap;padding: 5px;border: 1px solid white;z-index: 10000;}.jqsfield { color: white;font: 10px arial, san serif;text-align: left;}</style>
        <link href="../BootstrapXL.css" rel="stylesheet" type="text/css"/>
        <style type="text/css">
            .bs-example{
                margin: 20px;
            }
        </style>

    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 


                <div class="row" style="padding-top: 75px;">
                    
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse" href="#collapse1" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Picks Per Cubic Inch Calculation
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse1" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
         
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse"  href="#collapse2" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Walk Score Calculation
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse2" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
         
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse" href="#collapse3" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Location Slot Size Calculation
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse3" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
         
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse" href="#collapse4" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Replen Score Calculation
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse4" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
         
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse" href="#collapse5" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Loose Slotting Methodology
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse5" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
         
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse" href="#collapse6" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Pick Heat Map
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse6" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
         
                    <div class="col-sm-12">
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading" data-toggle="collapse" href="#collapse7" style="cursor: pointer;">
                                    <h4 class="panel-title">
                                        <a >
                                            Replen Heat Map
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse7" class="panel-collapse collapse">
                                    <div class="panel-body">
                                      
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>



            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});
            $("#help").addClass('active');

        </script>

    </body>
</html>


