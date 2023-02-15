<?php

//txt vystup z kros omega, cez firma export a export vystavenych faktur
function faonlinevfa2mrpks_generate()
{

  $sess_id = session_id();
  $target_dir = "tmp_uploads/";
  $nespracovane_fa = array();
  $start_found = 0;


  $txt_vydane_fa = $target_dir . $sess_id . '_' . $_FILES['f_csv']["name"];

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

      //hlavicka suboru pre import
      $export_data = '<?xml version="1.0" encoding="UTF-8"?><MRPKSData version="2.0" countryCode="SK" currencyCode="EUR"><IssuedInvoices>';

      while (($row_data = fgetcsv($handle, 0, )) !== FALSE) {

        while ($start_found == 0) {
          if ($row_data[0] == "Číslo faktúry") {
            $start_found = 1;
          }
          $row_data = fgetcsv($handle, 0, );
        }

        if (count($row_data) > 0) {

          $data = create_faktura($row_data);
          if ($data['result'] == -1) {
            $nespracovane_fa[]["hlavicka"] = $row_data;
          } else {
            $export_data .= $data['xml'];
            $spracovavana_fa_cislo = $data['fa_cislo'];
          }
        }
      }

      //uzavri data na export
      $export_data .= '</IssuedInvoices></MRPKSData>';

      //validuj a sparsuj xml data
      libxml_use_internal_errors(true);
      $xml = simplexml_load_string($export_data);
      if ($xml !== false) {
          //vystup
        file_put_contents($destdir . "/processed_" . $_FILES['f_csv']["name"] . ".xml", $xml->asXML());
      } else {
        echo ('Chyba pri validácii vytvoreného xml suboru:<br /><ul>');
        foreach (libxml_get_errors() as $error) {
          echo "<li>" . $error->message . "na riadku " . $error->line . " a pozicii " . $error->column . "</li>";
        }
        echo "</ul>";
        echo "<pre>" . $export_data . "</pre>";
        die('Kontaktujte autora apikácie');
      }
    } else {
      die("Error! Could not open uploaded CSV file:" . $_FILES['f_csv']["name"]);
    }
  } else {
    die("Error! Could not find uploaded CSV file:" . $_FILES['f_csv']["name"]);
  }
  //ak je vsetko ok posli spracovany subor klientovi
  return array(0, $destdir . "/processed_" . $_FILES['f_csv']["name"] . ".xml");
}

//rozseka row_data a vytvori z nej sekciu xml obsahujucu hlavicku faktury
function create_faktura($row_data)
{

  $xml_data = '<Invoice>';
  $data['fa_cislo'] = trim(substr($row_data[0], 0, 50));
  $xml_data .= '<OriginalDocumentNumber>' . $data['fa_cislo'] . '</OriginalDocumentNumber>';
  $xml_data .= '<IssueDate>' . $row_data[12] . '</IssueDate>';
  $xml_data .= '<CurrencyCode>' . substr($row_data[8], 0, 3) . '</CurrencyCode>';
  $xml_data .= '<ValuesWithTax>F</ValuesWithTax>';

  if (substr($row_data[27], 0, 2)== "SK"){
    $tax_code = '10';
  }else {
    $tax_code = '19';
    $xml_data .= '<RecapitulativeStatementCode>2</RecapitulativeStatementCode>';
  }

  $xml_data .= '<TaxCode>'.$tax_code.'</TaxCode>';

  //overit ci je to c100
  switch ($row_data[1]) {
    case "FAKTÚRA":
      $xml_data .= '<DocType> </DocType>';
      $xml_data .= '<InvoiceType>F</InvoiceType>';
      break;
  }

  if ($row_data[5] == $row_data[4]) {
    //bez dph
    $xml_data .= '<ZeroTaxRateAmount>' . number_format($row_data[4], 2, '.', '') . '</ZeroTaxRateAmount>';
  } else {
    //  $xml_data .= '<ReducedTaxRateAmount>' . number_format($row_data[7], 2, '.', '') . '</ReducedTaxRateAmount>';
    $xml_data .= '<BaseTaxRateAmount>' . number_format($row_data[4], 2, '.', '') . '</BaseTaxRateAmount>';
    //$xml_data .= '<RoundingAmount>' . number_format($row_data[15], 2, '.', '') . '</RoundingAmount>';
    //$xml_data .= '<ReducedTaxRateTax>' . number_format($row_data[13], 2, '.', '') . '</ReducedTaxRateTax>';
    $xml_data .= '<BaseTaxRateTax>' . number_format($row_data[5] - $row_data[4], 2, '.', '') . '</BaseTaxRateTax>';
  }
  $xml_data .= '<TotalWithTaxCurr>' . number_format($row_data[5], 2, '.', '') . '</TotalWithTaxCurr>';

  $xml_data .= '<TaxPointDate>' . $row_data[12] . '</TaxPointDate>';
  $xml_data .= '<DeliveryDate>' . $row_data[13] . '</DeliveryDate>';
  $xml_data .= '<PaymentDueDate>' . $row_data[14] . '</PaymentDueDate>';

  $xml_data .= '<VariableSymbol>' . substr($row_data[33], 0, 10) . '</VariableSymbol>';
  $xml_data .= '<ConstantSymbol>' . substr($row_data[32], 0, 8) . '</ConstantSymbol>';
  //$xml_data .= '<SpecificSymbol>' . substr($row_data[36], 0, 10) . '</SpecificSymbol>';
  //$xml_data .= '<DeliveryNoteID>' . substr($row_data[32], 0, 10) . '</DeliveryNoteID>';
  $xml_data .= '<PaymentMeansCode>' . substr($row_data[10], 0, 10) . '</PaymentMeansCode>';
  //$xml_data .= '<DeliveryTypeCode>' . substr($row_data[38], 0, 10) . '</DeliveryTypeCode>';
  //$xml_data .= '<OrderNumber>' . substr($row_data[33], 0, 20) . '</OrderNumber>';

  //Company
  $xml_data .= '<Company>';
  $xml_data .= '<CompanyId>' . substr($row_data[25], 0, 12) . '</CompanyId>';
  $xml_data .= '<Name>' . htmlspecialchars(substr($row_data[3], 0, 50)) . '</Name>';
  $xml_data .= '<Street>' . substr($row_data[28], 0, 30) . '</Street>';
  $xml_data .= '<City>' . substr($row_data[29], 0, 30) . '</City>';
  $xml_data .= '<Country>' . substr($row_data[31], 0, 30) . '</Country>';
  //$xml_data .= '<CountryCode>' . substr($row_data[47], 0, 2) . '</CountryCode>';
  $xml_data .= '<ZipCode>' . substr($row_data[30], 0, 15) . '</ZipCode>';
  $xml_data .= '<VatNumber>' . substr($row_data[26], 0, 17) . '</VatNumber>';
  $xml_data .= '<VatNumberSK>' . substr($row_data[27], 0, 14) . '</VatNumberSK>';
  //$xml_data .= '<Phone>' . substr($row_data[83], 0, 30) . '</Phone>';
  $xml_data .= '<Note>' . htmlspecialchars(substr($row_data[37], 0, 1024)) . '</Note>';

  $pattern = "/a\.s\.*$|s\.r\.o\.*$|v\.o\.s\.*$|k\.s\.*$/i";
  if (preg_match($pattern, trim($row_data[3]))) {
    $person_type = 'F';
  } else {
    $person_type = 'T';
  }
  $xml_data .= '<NaturalPerson>' . $person_type . '</NaturalPerson>';
  $xml_data .= '</Company>';
  $xml_data .= '<Items>';

  $data_pol = create_polozka($row_data, $tax_code);
  $xml_data .= $data_pol['xml'];

  $xml_data .= "</Items></Invoice>";

  $data['result'] = 0;
  $data['xml'] = $xml_data;

  return $data;
}
//rozseka rowdata a vrati onfo o polozke faktury vo formate xml
function create_polozka($row_data, $tax_code)
{
  $xml_data = "";
  $xml_data .= '<Item>';
  $xml_data .= '<Description>Prepravné služby</Description>';
  $xml_data .= '<RowType>1</RowType>';
  $xml_data .= '<UnitCode>ks</UnitCode>';
  $xml_data .= '<Quantity>1</Quantity>';
  $xml_data .= '<TaxCode>'.$tax_code.'</TaxCode>';
  $xml_data .= '<UnitPrice>' . number_format($row_data[4], 6, '.', '') . '</UnitPrice>';

  if ($row_data[5] == $row_data[4]) {
    $xml_data .= '<TaxPercent>0</TaxPercent>';
    $xml_data .= '<TaxAmount>0</TaxAmount>';
  } else {
    $xml_data .= '<TaxPercent>20</TaxPercent>';
    $xml_data .= '<TaxAmount>' . number_format(($row_data[5] - $row_data[4]), 6, '.', '') . '</TaxAmount>';
  }

  $xml_data .= '<RowSumType>1</RowSumType>';

  if($tax_code == "19"){

    $xml_data .= '<ItemType>S</ItemType>';

  }

  $xml_data .= '</Item>';

  $data['xml'] = $xml_data;

  return $data;
}
?>