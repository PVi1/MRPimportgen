<?php

//session_start();

function ecosun2mrpks_generate()
{
    $sess_id = session_id();
    $target_dir = "tmp_uploads/";
    $nespracovane_fa = array();
    $nespracovane_fa_pol = array();
    $typ_polozky = "S";


    $xml_vydane_fa = $target_dir . $sess_id . '_' . $_FILES['f_xml_fakodb']["name"];

    $destdir = "downloads/" . $sess_id;

    if (!is_dir($destdir)) {
        if (!mkdir($destdir, 0755)) {
            die("Nedokazem vytvorit adresar" . $destdir . ", kontaktujte spravcu");
        }
    }
    //vyknaj zmeny v xml a zapis novy subor do $destdir . "processed_".$_FILES['f_xml_fakodb']["name"];

    if (file_exists($xml_vydane_fa)) {
       $xml = simplexml_load_file($xml_vydane_fa);
       foreach ($xml->IssuedInvoices->Invoice as $invoice) {
         //nastavenie dp a typu polozky
              foreach ($invoice->Items->Item as $polozka) {
                if($polozka->StockCardNumber>0){
                  $polozka->RowSumType='3';
                }
              }
       }
      file_put_contents($destdir . "/processed_".$_FILES['f_xml_fakodb']["name"], $xml->asXML());
    }
    else {
       die("Error! Could not open uploaded XML file:".$_FILES['f_xml_fakodb']["name"]);
    }
    //ak je vsetko ok
    return array(0, $destdir . "/processed_".$_FILES['f_xml_fakodb']["name"]);
}
