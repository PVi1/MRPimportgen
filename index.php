<?php
session_start();

// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size() {
    static $max_size = -1;

    if ($max_size < 0) {
        // Start with post_max_size.
        $post_max_size = parse_size(ini_get('post_max_size'));
        if ($post_max_size > 0) {
            $max_size = $post_max_size;
        }

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

function sec_file_upload($f_name, $ext) {
    $sess_id = session_id();
    $target_dir = "tmp_uploads/";
    $target_file = $target_dir . $sess_id . '_' . basename($_FILES[$f_name]["name"]);
    $extension = pathinfo($target_file, PATHINFO_EXTENSION);

    if (strpos($_FILES[$f_name]["name"], '/') || strpos($_FILES[$f_name]["name"], '\\')) {
        return 4;
    }

    if (strcasecmp($extension, $ext)) {
        return 1;
    }
    // Check file size up to 50MB
    if ($_FILES[$f_name]["size"] > file_upload_max_size()) {
        echo "Sorry, your file is too large(Limit:" . file_upload_max_size() . "). Contact system administrator.";
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
    $sess_id = session_id();

    foreach ($farray as $ffile) {
        if (is_file($target_dir . $sess_id . '_' . $ffile)) {
            unlink($target_dir . $sess_id . '_' . $ffile);
        }
    }
}

function rrmdir($dir,$archive) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    rrmdir($dir . "/" . $object,$archive);
                } else if ($archive==0){
                    if(stripos($object, "mrp_import.zip") === false) {
                        unlink($dir . "/" . $object);
                    }
                }else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

function mrpkssklad(){
  echo '<header class="w3-container" style="padding-top:22px">
        <h5><b><i class="fa fa-dashboard"></i> Prevody do MRP K/S</b></h5>
    </header>
    <form method="post" target="index.php" enctype="multipart/form-data">
        <div class="w3-row-padding w3-margin-bottom">
            <div class="w3-threequarter">
                <h4>Pre úpravu vygenerovaných XML súborov pre import do MRP K/S, priložte nasledovné súbory</h4>
                <p>Súbor XML s vystavenými faktúrami: <input type="file" name="f_xml_fakodb" id="f_xml_fakodb"></input><i id="sf_xml_fakodb" aria-hidden="true" class="fa fa-square-o"></i></p>
                <hr>
                Kliknutím na nasledovné tlačidlo zahájite tvorbu XML súboru pre import do MRP: <input type="submit" class="button" name="generujks_sklad2007" value="Vygenerovať">
            </div>
        </div>
    </form>
    <div class="w3-panel">
        <div class="w3-row-padding" style="margin:0 -16px">
        </div>
    </div>';
}

function ecosunmrpks(){
  echo '<header class="w3-container" style="padding-top:22px">
        <h5><b><i class="fa fa-dashboard"></i> Prevod zo Sunsoft EcoSun K/S XML do MRP K/S</b></h5>
    </header>
    <form method="post" target="index.php" enctype="multipart/form-data">
        <div class="w3-row-padding w3-margin-bottom">
            <div class="w3-threequarter">
                <h4>Pre úpravu vygenerovaných XML súborov pre efektívnejši import do MRP K/S(typ polozky, typ sumy dph), priložte nasledovné súbory</h4>
                <p>Súbor XML s vystavenými faktúrami: <input type="file" name="f_xml_fakodb" id="f_xml_fakodb"></input><i id="sf_xml_fakodb" aria-hidden="true" class="fa fa-square-o"></i></p>
                <hr>
                Kliknutím na nasledovné tlačidlo zahájite tvorbu XML súboru pre import do MRP: <input type="submit" class="button" name="generujks_ecosun" value="Vygenerovať">
            </div>
        </div>
    </form>
    <div class="w3-panel">
        <div class="w3-row-padding" style="margin:0 -16px">
        </div>
    </div>';
}

function omegatxtfvydanemrpks(){
  echo '<header class="w3-container" style="padding-top:22px">
        <h5><b><i class="fa fa-dashboard"></i> Prevod vydaných faktúr z KROS OMEGA vo formáte TXT(CSV) do MRP K/S</b></h5>
    </header>
    <form method="post" target="index.php" enctype="multipart/form-data">
        <div class="w3-row-padding w3-margin-bottom">
            <div class="w3-threequarter">
                <h4>Pre vytvorenie XML súborov pre import vydaných faktúr do MRP K/S, priložte nasledovné súbory</h4>
                <p>Súbor TXT s vystavenými faktúrami: <input type="file" name="f_txt" id="f_txt"></input><i id="sf_txt" aria-hidden="true" class="fa fa-square-o"></i></p>
                <hr>
                Kliknutím na nasledovné tlačidlo zahájite tvorbu XML súboru pre import do MRP: <input type="submit" class="button" name="generujks_omegafv" value="Vygenerovať">
            </div>
        </div>
    </form>
    <div class="w3-panel">
        <div class="w3-row-padding" style="margin:0 -16px">
        </div>
    </div>';

}

function faonlinexlsxfvydanemrpks(){
    echo '<header class="w3-container" style="padding-top:22px">
          <h5><b><i class="fa fa-dashboard"></i> Prevod vydaných faktúr z Faktury-Online.com vo formáte XLSX do MRP K/S</b></h5>
      </header>
      <form method="post" target="index.php" enctype="multipart/form-data">
          <div class="w3-row-padding w3-margin-bottom">
              <div class="w3-threequarter">
                  <h4>Pre vytvorenie XML súborov pre import vydaných faktúr do MRP K/S, priložte nasledovné súbory</h4>
                  <p>Súbor XLSX s vystavenými faktúrami: <input type="file" name="f_csv" id="f_csv"></input><i id="sf_csv" aria-hidden="true" class="fa fa-square-o"></i></p>
                  <hr>
                  Kliknutím na nasledovné tlačidlo zahájite tvorbu XML súboru pre import do MRP K/S: <input type="submit" class="button" name="generujks_faonlinefv" value="Vygenerovať">
              </div>
          </div>
      </form>
      <div class="w3-panel">
          <div class="w3-row-padding" style="margin:0 -16px">
          </div>
      </div>';
  
  }

function generujks($typ){


      if(!isset($typ)){
          die('Nieje zvolený typ importu, kontaktuj správcu systému.');
      }

  //1. nacitat subory do tmp lokacie
      switch($typ){
        case 'sklad2007':
        case 'ecosun':
              if (!isset($_FILES['f_xml_fakodb'])) {
                  die('Nenahrali ste všetky požadované súbory');
              }
              if (isset($_FILES['f_xml_fakodb'])) {
                  $fu_res = sec_file_upload('f_xml_fakodb', "XML");
                  if ($fu_res) {
                      die('Problem pri nahravani XML suboru s vystavenými faktúrami, detail:' . $fu_res . '.');
                  }
              }
              break;
        case 'omegafv':
              if (!isset($_FILES['f_txt'])) {
                  die('Nenahrali ste všetky požadované súbory');
              }
              if (isset($_FILES['f_txt'])) {
                  $fu_res = sec_file_upload('f_txt', "TXT");
                  if ($fu_res) {
                      die('Problem pri nahravani TXT suboru s vystavenými faktúrami, detail:' . $fu_res . '.');
                  }
              }
              break;
        case 'faonlinefv':
                if (!isset($_FILES['f_xlsx'])) {
                    die('Nenahrali ste všetky požadované súbory');
                }
                if (isset($_FILES['f_xlsx'])) {
                    $fu_res = sec_file_upload('f_xlsx', "XLSX");
                    if ($fu_res) {
                        die('Problem pri nahravani XLSX suboru s vystavenými faktúrami, detail:' . $fu_res . '.');
                    }
                }
                break;
        default:
            die('Neznámy typ súboru, kontaktujte správcu systému.');
      }
  //2. spracovat obsah a vytvorit txt
      switch($typ){
        case 'sklad2007':
              require_once('sklad2mrpks.php');
              $res = sklad2mrpks_generate();
              break;
        case 'ecosun':
              require_once('ecosun2mrpks.php');
              $res = ecosun2mrpks_generate();
              break;
        case 'omegafv':
              require_once('omegavfa2mrpks.php');
              $res = omegavfa2mrpks_generate();
              break;
        case 'faonlinefv':
              require_once('faonlinevfa2mrpks.php');
              $res = faonlinevfa2mrpks_generate();
              break;
      }
      //3.vycisti po sebe
      switch($typ){
        case 'sklad2007':
        case 'ecosun':
              $ffiles = array($_FILES['f_xml_fakodb']['name']);
              clean_tmp($ffiles);
              break;
        case 'omegafv':
              $ffiles = array($_FILES['f_txt']['name']);
              clean_tmp($ffiles);
              break;
        case 'faonlinefv':
              $ffiles = array($_FILES['f_xlsx']['name']);
              clean_tmp($ffiles);
              break;
      }

      //4. vrati txt do browseru na ulozenie
      if ($res[0] == 0) {
          echo "<h3>Konverzia prebehla úspešne.</h3>";
      } else {
          echo "<h3>Pozor, nie všetky faktúry bolo možné importovať! Skontroluj obsah súboru FNespracovane.txt!</h3>";
      }
      echo "<p>Súbor s dátami pre import stiahnete <a href=\"" . $res[1] ."\" target=\"_blank\">tu</a></p>";    
      if(count($res[2])>0){
        echo "<p>V nasledovných faktúrach sa nachádza položka typu kľúčová služba:<br />";
        foreach ($res[2] as $faktura) {
          echo "<strong>&nbsp;&nbsp;&nbsp;&nbsp;{$faktura}</strong><br />";
        }
        echo "</p>";
      }
      echo "<p>Po stiahnutí súboru s archívom, odstránte všetky nahraté dáta týkajúce sa tohto prevodu zo servera, kliknutím <a href=\"index.php?action=delete\">sem</a></p>";

}


//spracovanie formulara
if (isset($_GET['action']) && $_GET["action"] == "delete") {
    $ses = session_id();
    if (strlen($ses) > 0) {
        rrmdir("downloads/" . $ses . "/",1);
        echo "Hotovo, citlivé dáta boli odstrátené.";
    }
} else if (isset($_POST['generujks_sklad2007'])) {
  //generuj sklad 2007 do mrp ks
  generujks('sklad2007');
} else if (isset($_POST['generujks_ecosun'])) {
  //generuj ecosun do rp ks
  generujks('ecosun');
} else if (isset($_POST['generujks_omegafv'])) {
  //generuj omega FV do mrp ks
  generujks('omegafv');
} else if (isset($_POST['generujks_faonlinefv'])) {
    //generuj faktura online FV do mrp ks
    generujks('faonlinefv');
}
 else {
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
                    <h5>Prevody do MRP K/S</h5>
                </div>
                <div class="w3-bar-block">
                    <a href="#" class="w3-bar-item w3-button w3-padding-16 w3-hide-large w3-dark-grey w3-hover-black" onclick="w3_close()" title="close menu"><i class="fa fa-remove fa-fw"></i>  Close Menu</a>
                    <a href="index.php?tool=mrpkssklad" class="w3-bar-item w3-button w3-padding"><i class="fa fa-users fa-fw"></i>APK SW - SPED (*.XML)</a>
                    <a href="index.php?tool=ecosunmrpks" class="w3-bar-item w3-button w3-padding"><i class="fa fa-users fa-fw"></i>Sunsoft ECOSUN (*.XML)</a>
                    <a href="index.php?tool=omegatxtfvydanemrpks" class="w3-bar-item w3-button w3-padding"><i class="fa fa-users fa-fw"></i>KROS OMEGA FA vydane (*.TXT)</a>
                    <a href="index.php?tool=faonlinexlsxfvydanemrpks" class="w3-bar-item w3-button w3-padding"><i class="fa fa-users fa-fw"></i>Faktury-online.com (*.XLSX)</a>
                    <br><br>
                </div>               
            </nav>


            <!-- Overlay effect when opening sidebar on small screens -->
            <div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

            <!-- !PAGE CONTENT! -->
            <div class="w3-main" style="margin-left:300px;margin-top:43px;">
            <?php
            if (isset($_GET['tool'])){
                $tool = htmlspecialchars(addslashes($_GET['tool']));

                switch($tool){

                    case 'mrpkssklad':
                            mrpkssklad();
                            break;
                    case 'ecosunmrpks':
                            ecosunmrpks();
                            break;
                    case 'omegatxtfvydanemrpks':
                            omegatxtfvydanemrpks();
                        break;
                    case 'faonlinexlsxfvydanemrpks':
                            faonlinexlsxfvydanemrpks();
                    break;
                }
            }
            ?>
                <hr>               
                    <!-- Footer -->
                    <footer class="w3-container w3-padding-16 w3-light-grey">

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

                                                    var inputName = $(this).attr('name');
                                                    var patMatch = "";
                                                    switch (inputName) {

                                                        case 'f_xml_fakodb':
                                                            fileName = fileName.split('.').pop();
                                                            patMatch = "xml";
                                                            break;
                                                        case 'f_txt':
                                                            fileName = fileName.split('.').pop();
                                                            patMatch = "txt";
                                                            break;
                                                        case 'f_csv':
                                                            fileName = fileName.split('.').pop();
                                                            patMatch = "csv";
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
