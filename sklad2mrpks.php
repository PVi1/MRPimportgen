<?php

//session_start();

function sklad2mrpks_generate()
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
         //kontrola chybne pridaneho icdph  -ak je v poli VatNumber = DIC alpha znak tak ju presun na VatNuberSK
         if(strlen($invoice->Company->VatNumber)>0 && preg_match('/^[a-zA-Z]{2}[^a-zA-Z]*/',$invoice->Company->VatNumber)){
             $invoice->Company->VatNumberSK = preg_replace('/\s+/','', $invoice->Company->VatNumber);
             $invoice->Company->VatNumber = '';
         }else {
           if(isset($invoice->Company->VatNumberSK)){
             $invoice->Company->VatNumberSK = preg_replace('/\s+/','', $invoice->Company->VatNumberSK);
           }else if(isset($invoice->Company->VatNumberSk)){
             $invoice->Company->VatNumberSK = preg_replace('/\s+/','', $invoice->Company->VatNumberSk);
           }
         }
         unset($invoice->Company->VatNumberSk);

         //nastavenie dp a typu polozky
         if ($invoice->TaxCode == "19"){
            $invoice->RecapitulativeStatementCode = "2";
            if(count($invoice->Items->Item)>1){
              foreach ($invoice->Items->Item as $polozka) {
                $polozka->ItemType = "S";
              }
            }
            else{
              $invoice->Items->Item->ItemType = "S";
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
