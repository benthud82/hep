<!DOCTYPE html>
<html>
    <?php
    include 'sessioninclude.php';
        include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Case Slottting</title>
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

                    <div class="pull-left" style="margin-left: 15px" >
                        <label>Items to Return: </label>
                        <select class="selectstyle" id="returncount" name="returncount"  style="width: 100px;">
                            <option value=1>1</option>
                            <option value=5>5</option>
                            <option value=10>10</option>
                        </select>
                    </div>
                    <div class="pull-left" style="margin-left: 15px" >
                        <button id="loaddata" type="button" class="btn btn-primary" onclick="gettable();" style="margin-bottom: 5px;">Load Data</button>
                    </div>
                </div>


                <div id="itemdetailcontainerloading" class="loading col-sm-12 text-center hidden" >
                    Data Loading <img src="../ajax-loader-big.gif"/>
                </div>
                <div id="itemdetailcontainer" class="col-md-12 hidden"></div>

                

            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});


            function gettable() {
                $('#itemdetailcontainer').addClass('hidden');
                $('#itemdetailcontainerloading').removeClass('hidden');
                var userid = $('#userid').text();
                var returncount = $('#returncount').val();
                var zone = 'CSE';

                $.ajax({
                    url: 'globaldata/whattodo.php',
                    data: {userid: userid, returncount: returncount, zone: zone},
                    type: 'POST',
                    dataType: 'html',
                    success: function (ajaxresult) {
                        $('#itemdetailcontainer').removeClass('hidden');
                        $('#itemdetailcontainerloading').addClass('hidden');
                        $("#itemdetailcontainer").html(ajaxresult);
                        fillwhseinputval(whsesel);  //fill the whse drop down
                        cleanurl();  //clean the URL of post data
                        showloaddata();  //deterime if load button should be displayed
                    }
                });
            }

            function GetUrlValue(VarSearch) {  //parse URL to pull variable defined
                var SearchString = window.location.search.substring(1);
                var VariableArray = SearchString.split('&');
                for (var i = 0; i < VariableArray.length; i++) {
                    var KeyValuePair = VariableArray[i].split('=');
                    if (KeyValuePair[0] === VarSearch) {
                        return KeyValuePair[1];
                    }
                }
            }

            function fillwhseinputval(whsesel) {  //fill whse dropdown
                document.getElementById("whsesel").value = whsesel;
            }

            function filliteminputval(itemnum) {  //fill item input text
                document.getElementById("itemnum").value = itemnum;
            }

            function cleanurl() { //clean the URL if called from another page
                var clean_uri = location.protocol + "//" + location.host + location.pathname;
                window.history.replaceState({}, document.title, clean_uri);
            }

//            $(document).on("click", ".clicktotoggle-chevron", function (e) {
//                $(this).toggleClass('fa-chevron-circle-down fa-chevron-circle-up');
//                $(this).closest('.media').next('.hiddencostdetail').slideToggle();
//            });

            $(document).on("click", ".itemopbutton", function () {
                debugger;
                $('#loading').hide();
                var passedid = $(this)[0].id;
                var itemnum = passedid.substring(0, 7); //itemcode
                var currtier = passedid.substring(7, 10); //current tier
                var pkgu = passedid.substring(10); //package unit
                var whsesel = $('#whsesel').val();
                $.ajax({
                    url: '../globaldata/maxtotfdata.php',
                    data: {itemnum: itemnum, pkgu: pkgu, currtier: currtier, whsesel: whsesel},
                    type: 'POST',
                    dataType: 'html',
                    beforeSend: function () {
                        $('#maxtotfcontainer' + passedid).hide();
                        $('#maxtotfloading' + passedid).show();
                    },
                    complete: function () {
                        $('#maxtotfloading' + passedid).hide();
                        $('#maxtotfcontainer' + passedid).show();
                    },
                    success: function (ajaxresult) {
                        $("#maxtotfcontainer" + passedid).html(ajaxresult);
                    }
                });
                $.ajax({
                    url: '../globallogic/itemsettingslogic.php',
                    data: {itemnum: itemnum, pkgu: pkgu, currtier: currtier, whsesel: whsesel},
                    type: 'POST',
                    dataType: 'html',
                    beforeSend: function () {
                        $('#itemsettingscontainer' + passedid).hide();
                        $('#itemsettingsloading' + passedid).show();
                    },
                    complete: function () {
                        $('#itemsettingsloading' + passedid).hide();
                        $('#itemsettingscontainer' + passedid).show();
                    },
                    success: function (ajaxresult) {
                        $("#itemsettingscontainer" + passedid).html(ajaxresult);
                    }
                });
                $.ajax({
                    url: '../globallogic/caseeachopplogic.php',
                    data: {itemnum: itemnum, pkgu: pkgu, currtier: currtier, whsesel: whsesel},
                    type: 'POST',
                    dataType: 'html',
                    beforeSend: function () {
                        $('#caseeachcontainer' + passedid).hide();
                        $('#caseeachloading' + passedid).show();
                    },
                    complete: function () {
                        $('#caseeachloading' + passedid).hide();
                        $('#caseeachcontainer' + passedid).show();
                    },
                    success: function (ajaxresult) {
                        $("#caseeachcontainer" + passedid).html(ajaxresult);
                    }
                });
            });</script>



        <script>
            $("#case").addClass('active');
        </script>
    </body>
</html>
