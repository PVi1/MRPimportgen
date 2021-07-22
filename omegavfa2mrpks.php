<?php

//txt vystup z kros omega, cez firma export a export vystavenych faktur
function omegavfa2mrpks_generate() {

    $sess_id = session_id();
    $target_dir = "tmp_uploads/";
    $nespracovane_fa = array();
    $nespracovane_fa_pol = array();
    $typ_poslednej_polozky = "";


    $txt_vydane_fa = $target_dir . $sess_id . '_' . $_FILES['f_txt']["name"];

    $destdir = "downloads/" . $sess_id;
    $typ = 0;
    $spracovavana_fa_cislo = "";


    if (!is_dir($destdir)) {
        if (!mkdir($destdir, 0755)) {
            die("Nedokazem vytvorit adresar" . $destdir . ", kontaktujte spravcu");
        }
    }
    //vyknaj zmeny v xml a zapis novy subor do $destdir . "processed_".$_FILES['f_xml_fakodb']["name"];

    if (file_exists($txt_vydane_fa)) {

      if (($handle = fopen($txt_vydane_fa, "r")) !== FALSE) {
        stream_filter_append($handle, 'convert.iconv.windows-1250.utf-8');

        //hlavicka suboru pre import
        $export_data = '<?xml version="1.0" encoding="UTF-8"?><MRPKSData version="2.0" countryCode="CZ" currencyCode="CZK"><IssuedInvoices>';

            while (($row_data = fgetcsv($handle,0,"\t")) !== FALSE) {

              if(count($row_data)>0){


                if ($row_data[0] == "R00"){
                  switch($row_data[1]){
                    case "TOO":
                        die("Nenahrali ste správny typ exportu z programu Kros OMEGA, pokúšate sa nahrať typ 'Doklady EUD' !");
                        break;
                    case "T01":
                        $typ = 1;
                        break;
                    case "TO2":
                        die("Nenahrali ste správny typ exportu z programu Kros OMEGA, pokúšate sa nahrať typ 'Pohyby na sklade' !");
                        break;
                    case "TO3":
                        die("Nenahrali ste správny typ exportu z programu Kros OMEGA, pokúšate sa nahrať typ 'Skladové karty' !");
                        break;
                    case "TO4":
                        die("Nenahrali ste správny typ exportu z programu Kros OMEGA, pokúšate sa nahrať typ 'Partneri' !");
                        break;
                    default:
                        die("Nenahrali ste známy typ exportu z programu Kros OMEGA!");
                  }
                }
                else {
                    //vystavena faktura
                    if($typ == 1){
                      switch($row_data[0]){
                        case "R01":
                              if($typ_poslednej_polozky == 2 && $fa_data != ""){
                                //uzavri fakturu a zapis data o predch fakture do xml
                                $export_data .= $fa_data."</Items></Invoice>";
                              }
                            //hlavicka vystavenej faktury
                              $data = create_faktura($row_data);
                              if($data['result'] == -1){
                                $nespracovane_fa[]["hlavicka"] = $row_data;
                              }else {
                                $typ_poslednej_polozky = 1;
                                $fa_data = $data['xml'];
                                $spracovavana_fa_cislo = $data['fa_cislo'];
                                $mj_cena_s_bez_dan = "";
                              }
                              break;
                        case "R02":
                              //polozka vystavenej faktury
                              if($fa_data == ""){
                                $nespracovane_fa_pol[]["polozka"] = $row_data;
                                break;
                              }
                              /*
                              vrati sktrukturu s polozkami
                              result 0 - OK, -1 Problem
                              xml - xml struktura hlavicky fa
                              mjvat - T/F
                              */

                              $data = create_polozka($row_data);
                              if($data['result'] == -1){
                                $nespracovane_fa_pol[]["cislo"] = $spracovavana_fa_cislo;
                                $nespracovane_fa_pol[]["polozka"] = $row_data;
                                $fa_data ="";
                              }else {
                                if($mj_cena_s_bez_dan == "" && $typ_poslednej_polozky== 1){
                                  //prilep info o tom ci je cena polozky s alebo bez dane a uzavri hlavicku
                                  $mj_cena_s_bez_dan = $data['mjvat'];
                                    $fa_data .= '<ValuesWithTax>'.$mj_cena_s_bez_dan.'</ValuesWithTax>';
                                    $fa_data .= '<Items>';
                                }

                                $typ_poslednej_polozky = 2;
                                $fa_data .= $data['xml'];

                              }
                              break;
                      }
                    }
                }
              }
            }
            //zapis poslednu polozku posledne faktury ak bola ok
            if($fa_data != -1){
              $export_data .= $fa_data."</Items></Invoice>";
            }
            //uzavri data na export
            $export_data .= '</IssuedInvoices></MRPKSData>';

            //validuj a sparsuj xml data
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($export_data);
            if ($xml !== false){
              //vystup
              file_put_contents($destdir . "/processed_".$_FILES['f_txt']["name"].".xml", $xml->asXML());
            }else {
              echo('Chyba pri validácii vytvoreného xml suboru:<br /><ul>');
              foreach(libxml_get_errors() as $error) {
                echo "<li>" . $error->message . "na riadku ".$error->line." a pozicii ".$error->column."</li>";
              }
              echo "</ul>";
              echo "<pre>".$export_data_utf8."</pre>";
              die('Kontaktujte autora apikácie');
            }
      }
      else{
          die("Error! Could not open uploaded TXT file:".$_FILES['f_txt']["name"]);
      }
    }
    else {
       die("Error! Could not find uploaded TXT file:".$_FILES['f_txt']["name"]);
    }
    //ak je vsetko ok posli spracovany subor klientovi
    return array(0, $destdir . "/processed_".$_FILES['f_txt']["name"].".xml");
}

//rozseka row_data a vytvori z nej sekciu xml obsahujucu hlavicku faktury
function create_faktura($row_data){

  $xml_data = '<Invoice>';
  $data['fa_cislo'] = substr($row_data[1],0,10);
  $xml_data .= '<DocumentNumber>'.$data['fa_cislo'].'</DocumentNumber>';

  $datum_vyst = date_create_from_format('d.m.Y', $row_data[4]);
  if(!$datum_vyst){
    return $data['result'] = -1;
  }
  $xml_data .= '<IssueDate>'.date_format($datum_vyst,'Y-m-d').'</IssueDate>';
  $xml_data .= '<CurrencyCode>'.substr($row_data[39],0,3).'</CurrencyCode>';
  $xml_data .= '<ValuesWithTax>F</ValuesWithTax>';
  $xml_data .= '<TaxCode>10</TaxCode>';

//overit ci je to c100
  switch($row_data[17]){
    case "0":
      $xml_data .= '<DocType> </DocType>';
      $xml_data .= '<InvoiceType>F</InvoiceType>';
      break;
    case "1":
      $xml_data .= '<InvoiceType>X</InvoiceType>';
      break;
    case "4":
      $xml_data .= '<DocType>D</DocType>';
      break;
    case "9":
      $xml_data .= '<InvoiceType>P</InvoiceType>';
      break;
    }

  $xml_data .= '<ZeroTaxRateAmount>'.number_format($row_data[9], 2, '.', '').'</ZeroTaxRateAmount>';
  $xml_data .= '<ReducedTaxRateAmount>'.number_format($row_data[7], 2, '.', '').'</ReducedTaxRateAmount>';
  $xml_data .= '<BaseTaxRateAmount>'.number_format($row_data[8], 2, '.', '').'</BaseTaxRateAmount>';
  $xml_data .= '<RoundingAmount>'.number_format($row_data[15], 2, '.', '').'</RoundingAmount>';
  $xml_data .= '<ReducedTaxRateTax>'.number_format($row_data[13], 2, '.', '').'</ReducedTaxRateTax>';
  $xml_data .= '<BaseTaxRateTax>'.number_format($row_data[14], 2, '.', '').'</BaseTaxRateTax>';
  $xml_data .= '<TotalWithTaxCurr>'.number_format($row_data[16], 2, '.', '').'</TotalWithTaxCurr>';
  $datum_uzp = date_create_from_format('d.m.Y', $row_data[6]);
  if(!$datum_uzp){
    return $data['result'] = -1;
  }
  $xml_data .= '<TaxPointDate>'.date_format($datum_uzp,'Y-m-d').'</TaxPointDate>';
  $xml_data .= '<DeliveryDate>'.date_format($datum_uzp,'Y-m-d').'</DeliveryDate>';

  $datum_pay = date_create_from_format('d.m.Y', $row_data[6]);
  if(!$datum_pay){
    return $data['result'] = -1;
  }
  $xml_data .= '<PaymentDueDate>'.date_format($datum_pay,'Y-m-d').'</PaymentDueDate>';

  $xml_data .= '<VariableSymbol>'.substr($row_data[70],0,10).'</VariableSymbol>';
  $xml_data .= '<ConstantSymbol>'.substr($row_data[35],0,8).'</ConstantSymbol>';
  $xml_data .= '<SpecificSymbol>'.substr($row_data[36],0,10).'</SpecificSymbol>';
  $xml_data .= '<DeliveryNoteID>'.substr($row_data[32],0,10).'</DeliveryNoteID>';
  $xml_data .= '<PaymentMeansCode>'.substr($row_data[37],0,10).'</PaymentMeansCode>';
  $xml_data .= '<DeliveryTypeCode>'.substr($row_data[38],0,10).'</DeliveryTypeCode>';
  $xml_data .= '<OrderNumber>'.substr($row_data[33],0,20).'</OrderNumber>';

  $round_data = "";
  $round_method = "";
  $round_item = "";

  switch($row_data[60]){
    case "0":
      $round_data .= 'TRU=1.0;';
      break;
    case "1":
      $round_data .= 'TRU=0.1;';
      break;
    case "2":
      $round_data .= 'TRU=0.01;';
      break;
    case "3":
      $round_data .= 'TRU=0.001;';
      break;
    case "4":
      $round_data .= 'TRU=0.0001;';
      break;
    default:
      $round_data .= 'TRU=0.01;';
      break;
  }

  switch($row_data[61]){
    case "1":
      $round_method .= 'TRM=2;';
      break;
    case "2":
      $round_method .= 'TRM=1;';
      break;
    case "3":
      $round_method .= 'TRM=0;';
      break;
  }

  switch($row_data[63]){
    case "1":
      $round_item .= 'UPDP=1';
      break;
    case "2":
      $round_item .= 'UPDP=2';
      break;
    case "3":
      $round_item .= 'UPDP=3';
      break;
    case "4":
      $round_item .= 'UPDP=4';
      break;
  }

  $round_string = $round_data.$round_method.$round_item;
  if($round_string[strlen($round_string)-1]==';'){
    $round_string = rtrim($round_string,';');
  }

  $xml_data .= '<CalcParams>'.$round_string.'</CalcParams>';

//Company
  $xml_data .= '<Company>';
  $xml_data .= '<CompanyId>'.substr($row_data[3],0,12).'</CompanyId>';
  $xml_data .= '<Name>'.htmlspecialchars(substr($row_data[2],0,50)).'</Name>';
  $xml_data .= '<Street>'.substr($row_data[24],0,30).'</Street>';
  $xml_data .= '<City>'.substr($row_data[26],0,30).'</City>';
  $xml_data .= '<Country>'.substr($row_data[46],0,30).'</Country>';
  $xml_data .= '<CountryCode>'.substr($row_data[47],0,2).'</CountryCode>';
  $xml_data .= '<ZipCode>'.substr($row_data[25],0,15).'</ZipCode>';
  $xml_data .= '<VatNumber>'.substr($row_data[27],0,17).'</VatNumber>';
  $xml_data .= '<VatNumberSK>'.substr($row_data[47],0,2).substr($row_data[48],0,12).'</VatNumberSK>';
  $xml_data .= '<Phone>'.substr($row_data[83],0,30).'</Phone>';
  $xml_data .= '<Note>'.htmlspecialchars(substr($row_data[44],0,1024)).'</Note>';

  $pattern = "/a.s.$|s.r.o.$|v.o.s.$|k.s.$/i";
  if(preg_match($pattern, trim($row_data[2]))){
    $person_type = 'F';
  }
  else {
    $person_type = 'T';
  }
  $xml_data .= '<NaturalPerson>'.$person_type.'</NaturalPerson>';
  $xml_data .= '</Company>';


  $data['result'] = 0;
  $data['xml'] = $xml_data;

  return $data;
}
//rozseka rowdata a vrati onfo o polozke faktury vo formate xml
function create_polozka($row_data){
  $xml_data = "";
  $xml_data .= '<Item>';
  $xml_data .= '<Description>'.htmlspecialchars(substr($row_data[1],0,100)).'</Description>';
  $xml_data .= '<RowType>1</RowType>';
  $xml_data .= '<UnitCode>'.substr($row_data[3],0,3).'</UnitCode>';
  $xml_data .= '<Quantity>'.number_format($row_data[2], 6, '.', '').'</Quantity>';
  $xml_data .= '<UnitPrice>'.number_format($row_data[4], 6, '.', '').'</UnitPrice>';

  switch($row_data[5]){
    case '0':
        $xml_data .= '<TaxPercent>0</TaxPercent>';
        $xml_data .= '<TaxAmount>0</TaxAmount>';
        break;
    case 'V':
    if($row_data[50]>0 && $row_data[4]>0){
        $xml_data .= '<TaxPercent>'.number_format((str_replace(',','.',$row_data[50])/$row_data[4])*100-100, 0, '.', '').'</TaxPercent>';
      }else {
        $xml_data .= '<TaxPercent>20</TaxPercent>';
      }
        $xml_data .= '<TaxAmount>'.number_format((str_replace(',','.',$row_data[50])-$row_data[4]), 6, '.', '').'</TaxAmount>';
        break;
    case 'N':
      if($row_data[50]>0 && $row_data[4]>0){
          $xml_data .= '<TaxPercent>'.number_format((str_replace(',','.',$row_data[50])/$row_data[4])*100-100, 0, '.', '').'</TaxPercent>';
        }
      else{
        $xml_data .= '<TaxPercent>10</TaxPercent>';
      }
        $xml_data .= '<TaxAmount>'.number_format((str_replace(',','.',$row_data[50])-$row_data[4]), 6, '.', '').'</TaxAmount>';
        break;
    case 'X':
        $xml_data .= '<TaxPercent>99</TaxPercent>';
        break;
        $xml_data .= '<TaxAmount>0</TaxAmount>';
  }

    $xml_data .= '<DiscountPercent>'.number_format($row_data[8], 2, '.', '').'</DiscountPercent>';
    $xml_data .= '<UnitDiscount>'.number_format(str_replace(',','.',$row_data[51]), 6, '.', '').'</UnitDiscount>';
    $xml_data .= '<RowSumType>1</RowSumType>';

  $xml_data .= '</Item>';
  //ak je desctiption dlhssi ako 100, tak sprav dalsie riadky ale textove
  $start = 100;

  while($start<strlen($row_data[1])){
    $xml_data .= '<Item>';
    $xml_data .= '<Description>'.htmlspecialchars(substr($row_data[1],$start,100)). '</Description>';
    $xml_data .= '<RowType>2</RowType>';
    $xml_data .= '</Item>';
    $start += 100;
  }

  switch($row_data[49]){
    //s dph
    case '-1':
        $data['mjvat']= 'T';
        break;
    //bez dph
    case '0':
        $data['mjvat']= 'F';
        break;
  }

  $data['result'] = 0;
  $data['xml'] = $xml_data;
  return $data;
}
?>
