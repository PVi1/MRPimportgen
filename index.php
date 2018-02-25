<?php

function sec_file_upload($f_name, $ext) {
    $target_dir = "tmp_uploads/";
    $target_file = $target_dir . basename($_FILES[$f_name]["name"]);
    $extension = pathinfo($target_file, PATHINFO_EXTENSION);

    if (strpos($_FILES[$f_name]["name"], '/') || strpos($_FILES[$f_name]["name"], '\\')) {
        return 4;
    }

    if (strcasecmp($extension, $ext)) {
        return 1;
    }
    // Check file size up to 50MB
    if ($_FILES["fileToUpload"]["size"] > 50000000) {
        echo "Sorry, your file is too large.";
        return 2;
    }
    if (move_uploaded_file($_FILES[$f_name]["tmp_name"], $target_file)) {
        return 0;
    } else {
        return 3;
    }
}

function clean_tmp($farray) {
    $target_dir = "tmp_uploads/";
    foreach ($farray as $ffile) {
        if (is_file($target_dir.$ffile)) {
            unlink($target_dir.$ffile);
        }
    }
}

if (isset($_POST['generuj_sklad2007'])) {
    //1. nacitat subory do tmp lokacie
    if (isset($_FILES['f_adresy']) && isset($_FILES['f_fakodb']) && isset($_FILES['f_fotext'])){
        if (isset($_FILES['f_adresy'])) {
            $fu_res = sec_file_upload('f_adresy', "DBF");
            if ($fu_res) {
                die('Problem pri nahravani suboru s adresami, detail:' . $fu_res . '.');
            }
        }
        if (isset($_FILES['f_fakodb'])) {
            $fu_res = sec_file_upload('f_fakodb', "DBF");
            if ($fu_res) {
                die('Problem pri nahravani suboru s vystavenými faktúrami, detail:' . $fu_res . '.');
            }
        }
        if (isset($_FILES['f_fotext'])) {
            $fu_res = sec_file_upload('f_fotext', "DBF");
            if ($fu_res) {
                die('Problem pri nahravani suboru s položkami faktúr, detail:' . $fu_res . '.');
            }
        }

        //2. spracovat obsah a vytvorit txt
        require_once('sklad2mrp.php');
        sklad_generate_txt();

        //3.vycisti po sebe
        $ffiles=array("adresy.DBF","fakodb.DBF","fotext.DBF");
        clean_tmp($ffiles);
        //4. vrati txt do browseru na ulozenie
    } else {
        die('Nenahrali ste všetky požadované súbory');
    }
}else {
?>
<!DOCTYPE html>
<html>
    <title>Generátor súborov pre import do MRP</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <style>
        html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
    </style>
    <body class="w3-light-grey">

        <!-- Top container -->
        <div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
            <button class="w3-bar-item w3-button w3-hide-large w3-hover-none w3-hover-text-light-grey" onclick="w3_open();"><i class="fa fa-bars"></i>  Menu</button>
            <span class="w3-bar-item w3-right">Logo</span>
        </div>

        <!-- Sidebar/menu -->
        <nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar"><br>
            <div class="w3-container w3-row">
                <div class="w3-col s4">

                </div>
                <div class="w3-col s8 w3-bar">
                    <span>Vitajte!</span><br>            
                </div>
            </div>
            <hr>
            <div class="w3-container">
                <h5>Prevody do MRP cez .TXT</h5>
            </div>
            <div class="w3-bar-block">
                <a href="#" class="w3-bar-item w3-button w3-padding-16 w3-hide-large w3-dark-grey w3-hover-black" onclick="w3_close()" title="close menu"><i class="fa fa-remove fa-fw"></i>  Close Menu</a>
                <a href="#" class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-users fa-fw"></i>  Sklad2007 (*.DBF)</a>   
                <br><br>
            </div>
        </nav>


        <!-- Overlay effect when opening sidebar on small screens -->
        <div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

        <!-- !PAGE CONTENT! -->
        <div class="w3-main" style="margin-left:300px;margin-top:43px;">

            <!-- Header -->
            <header class="w3-container" style="padding-top:22px">
                <h5><b><i class="fa fa-dashboard"></i> Prevody do MRP cez .TXT</b></h5>
            </header>
            <form method="post" target="index.php" enctype="multipart/form-data">
                <div class="w3-row-padding w3-margin-bottom">
                    <div class="w3-half">
                        <h4>Pre vygenerovanie súborov pre import do MRP priložte nasledovné súbory</h4>
                        <p>Súbor s adresami (adresy.DBF): <input type="file" name="f_adresy" id="f_adresy"></input><i id="sf_adresy" aria-hidden="true" class="fa fa-square-o"></i></p>
                        <p>Súbor s vystavenými faktúrami (fakodb.DBF): <input type="file" name="f_fakodb" id="f_fakodb"></input><i id="sf_fakodb" aria-hidden="true" class="fa fa-square-o"></i></p>
                        <p>Súbor s položkami faktúr (fotext.DBF): <input type="file" name="f_fotext" id="f_fotext"></input><i id="sf_fotext" aria-hidden="true" class="fa fa-square-o"></i></p>
                        <hr>
                        Kliknutím na nasledovné tlačidlo zahájite tvorbu TXT súboru pre import do MRP: <input type="submit" class="button" name="generuj_sklad2007" value="Vygenerovať">

                    </div>   
                </div>
            </form>
            <div class="w3-panel">
                <div class="w3-row-padding" style="margin:0 -16px">
                </div>
            </div>

            <hr>
            <div class="w3-container">
                <h5>General Stats</h5>
                <p>New Visitors</p>
                <div class="w3-grey">
                    <div class="w3-container w3-center w3-padding w3-green" style="width:25%">+25%</div>
                </div>


                <div class="w3-container">

                </div>
                <hr>
                <div class="w3-container">

                </div>
                <hr>

                <div class="w3-container">


                </div>

                <!-- Footer -->
                <footer class="w3-container w3-padding-16 w3-light-grey">
                    <h4>FOOTER</h4>
                    <p>Created by <a href="https://www.itriesenia.eu/" target="_blank">PVi1<a></p>
                                <p>Template by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a></p>    
                                </footer>

                                <!-- End page content -->
                                </div>

                                <script>
                                    // Get the Sidebar
                                    var mySidebar = document.getElementById("mySidebar");

                                    // Get the DIV with overlay effect
                                    var overlayBg = document.getElementById("myOverlay");

                                    // Toggle between showing and hiding the sidebar, and add overlay effect
                                    function w3_open() {
                                        if (mySidebar.style.display === 'block') {
                                            mySidebar.style.display = 'none';
                                            overlayBg.style.display = "none";
                                        } else {
                                            mySidebar.style.display = 'block';
                                            overlayBg.style.display = "block";
                                        }
                                    }

                                    // Close the sidebar with the close button
                                    function w3_close() {
                                        mySidebar.style.display = "none";
                                        overlayBg.style.display = "none";
                                    }

                                    $(document).ready(function () {
                                        var go = 0;
                                        $(function () {
                                            $("input:file").change(function () {
                                                var fileName = $(this).val().split(/[\\ ]+/).pop();
                                                ;
                                                var inputName = $(this).attr('name');
                                                var patMatch = "";
                                                switch (inputName) {
                                                    case 'f_adresy':
                                                        patMatch = "adresy.DBF";
                                                        break;
                                                    case 'f_fakodb':
                                                        patMatch = "fakodb.DBF";
                                                        break;
                                                    case 'f_fotext':
                                                        patMatch = "fotext.DBF";
                                                        break;
                                                }
                                                if (fileName === patMatch) {
                                                    go = 1;
                                                    $('#s' + inputName).removeClass("fa fa-square-o");
                                                    $('#s' + inputName).addClass("fa fa-check-square-o");
                                                } else {
                                                    go = 0;
                                                    $('#s' + inputName).removeClass("fa fa-check-square-o");
                                                    $('#s' + inputName).addClass("fa fa-square-o");
                                                }

                                            });
                                        });
                                        $("form").submit(function (event) {
                                            if (go == 1) {
                                                //posli form
                                                alert('Posielam');

                                                return;
                                            } else {

                                                alert('Neplatne data na vstupe');
                                                event.preventDefault();
                                            }
                                        });
                                    });
                                </script>

                                </body>
                                </html>
<?php
}

?>