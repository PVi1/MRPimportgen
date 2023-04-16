<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

function uzavri_fakturu($totals){

  $xml_data ="</Items>";
  $xml_data .= '<ZeroTaxRateAmount>'.number_format($totals["0"], 2, '.', '').'</ZeroTaxRateAmount>';
  $xml_data .= '<ReducedTaxRateAmount>'.number_format($totals["10"], 2, '.', '').'</ReducedTaxRateAmount>';
  $xml_data .= '<BaseTaxRateAmount>'.number_format($totals["20"], 2, '.', '').'</BaseTaxRateAmount>';
  $xml_data .= '<RoundingAmount>'.number_format($totals["99"], 2, '.', '').'</RoundingAmount>';

  $xml_data .= '<ReducedTaxRateTax>'.number_format($totals["10"]*0.1, 2, '.', '').'</ReducedTaxRateTax>';
  $xml_data .= '<BaseTaxRateTax>'.number_format($totals["20"]*0.2, 2, '.', '').'</BaseTaxRateTax>';
  $xml_data .= '</Invoice>';

  return $xml_data;
}

function zacni_fakturu($row_data,&$totals){

    $totals["0"]=0;
    $totals["10"]=0;
    $totals["20"]=0;
    $totals["99"]=0;
    $totals["item_type"]="";

    $xml_data ="<Invoice>";
    $data['fa_cislo'] = trim(substr($row_data[0], 0, 50));
    $xml_data .= '<OriginalDocumentNumber>' . $data['fa_cislo'] . '</OriginalDocumentNumber>';
    
    $datum_vyst = date_create_from_format('Y-m-d', $row_data[12]);
    if(!$datum_vyst){
      return $data['result'] = -1;
    }
    $xml_data .= '<IssueDate>'.date_format($datum_vyst,'Y-m-d').'</IssueDate>';
    $xml_data .= '<CurrencyCode>'.substr(trim($row_data[8]),0,3).'</CurrencyCode>';
    $xml_data .= '<ValuesWithTax>F</ValuesWithTax>';
    
  if (substr(trim($row_data[27]), 0, 2)== "SK"){
    $tax_code = '10';
  }else {
    $tax_code = '19';
    $xml_data .= '<RecapitulativeStatementCode>2</RecapitulativeStatementCode>';
    $totals['item_type'] = "S";
  }

  $xml_data .= '<TaxCode>'.$tax_code.'</TaxCode>';

  
  
    switch(trim($row_data[1])){
      case "FAKTÚRA":
        $xml_data .= '<DocType> </DocType>';
        $xml_data .= '<InvoiceType>F</InvoiceType>';
        break;     
      }
  

    $datum_uzp = date_create_from_format('Y-m-d', trim($row_data[13]));
    if(!$datum_uzp){
      return $data['result'] = -1;
    }
    $xml_data .= '<TaxPointDate>'.date_format($datum_uzp,'Y-m-d').'</TaxPointDate>';
    $xml_data .= '<DeliveryDate>'.date_format($datum_uzp,'Y-m-d').'</DeliveryDate>';
  
    $datum_pay = date_create_from_format('Y-m-d', trim($row_data[14]));
    if(!$datum_pay){
      return $data['result'] = -1;
    }
    $xml_data .= '<PaymentDueDate>'.date_format($datum_pay,'Y-m-d').'</PaymentDueDate>';
  
    $xml_data .= '<VariableSymbol>'.substr(trim($row_data[33]),0,10).'</VariableSymbol>';
    $xml_data .= '<ConstantSymbol>'.substr(trim($row_data[32]),0,8).'</ConstantSymbol>';
    $xml_data .= '<PaymentMeansCode>'.substr(trim($row_data[10]),0,10).'</PaymentMeansCode>';
    $xml_data .= '<OrderNumber>'.substr(trim($row_data[34]),0,20).'</OrderNumber>';
  
    $round_data = "";
    $round_method = "";
    $round_item = "";
  
    $round_data .= 'TRU=0.01;';
    $round_method .= 'TRM=0;';
    $round_item .= 'UPDP=3';
       
  
    $round_string = $round_data.$round_method.$round_item;
    if($round_string[strlen($round_string)-1]==';'){
      $round_string = rtrim($round_string,';');
    }
  
    $xml_data .= '<CalcParams>'.$round_string.'</CalcParams>';
  
  //Company
    $xml_data .= '<Company>';
    $xml_data .= '<CompanyId>'.substr(trim($row_data[25]),0,12).'</CompanyId>';
    $xml_data .= '<Name>'.htmlspecialchars(substr(trim($row_data[3]),0,50)).'</Name>';
    $xml_data .= '<Street>'.substr($row_data[28],0,30).'</Street>';
    $xml_data .= '<City>'.substr($row_data[29],0,30).'</City>';
    $xml_data .= '<Country>'.substr($row_data[31],0,30).'</Country>';
    $xml_data .= '<ZipCode>'.substr($row_data[30],0,15).'</ZipCode>';
    $xml_data .= '<VatNumber>'.substr(trim($row_data[26]),0,17).'</VatNumber>';
    $xml_data .= '<VatNumberSK>'.substr(trim($row_data[27]),0,14).'</VatNumberSK>';
    $xml_data .= '<Note>'.htmlspecialchars(substr($row_data[37]." ".$row_data[38],0,1024)).'</Note>';
  
    $pattern = "/a\.[ ]*s\.$|s\.r\.o\.$|v\.o\.s\.$|k\.s\.$|štátny podnik$|spol\. s r\.o\.$/i";
    if(preg_match($pattern, trim($row_data[3]))){
      $person_type = 'F';
    }
    else {
      $person_type = 'T';
    }
    $xml_data .= '<NaturalPerson>'.$person_type.'</NaturalPerson>';
    $xml_data .= '</Company>';
    $xml_data .= '<Items>';
  
  
    $data['result'] = 0;
    $data['xml'] = $xml_data;
  
    return $data;

}

function polozka($row_data,&$totals){

  $xml_data = "";
  $xml_data .= '<Item>';

  if(strlen($row_data[2])<=100){
    $start_next = 99;
  } else {    
    $start_next = strrpos(substr($row_data[2],0,100)," ",-1);
    if($start_next === false){
      $start_next = 99;
    }
}
  
  $xml_data .= '<Description>'.htmlspecialchars(substr($row_data[2],0,$start_next+1)).'</Description>';
  $xml_data .= '<RowType>1</RowType>';
  $xml_data .= '<UnitCode>'.substr(trim($row_data[4]),0,3).'</UnitCode>';
  $xml_data .= '<Quantity>'.number_format(floatval(trim($row_data[3])), 6, '.', '').'</Quantity>';
  $xml_data .= '<UnitPrice>'.number_format(floatval(trim($row_data[5])), 6, '.', '').'</UnitPrice>';
  
  if(isset($totals['item_type'])){
        $xml_data .= '<ItemType>S</ItemType>';
  }

  switch($row_data[6]){
    case '0':
        $xml_data .= '<TaxPercent>0</TaxPercent>';
        $xml_data .= '<TaxAmount>0</TaxAmount>';
        $totals["0"] += trim($row_data[3]) * trim($row_data[5]);
        break;
    case '20':
   
        $xml_data .= '<TaxPercent>20</TaxPercent>';      
        $xml_data .= '<TaxAmount>'.number_format((str_replace(',','.',trim($row_data[7]))-str_replace(',','.',trim($row_data[5]))), 6, '.', '').'</TaxAmount>';
        $totals["20"] += trim($row_data[3]) * trim($row_data[5]);
        break;        
    case '10':
        $xml_data .= '<TaxPercent>10</TaxPercent>';
        $xml_data .= '<TaxAmount>'.number_format((str_replace(',','.',trim($row_data[7]))-str_replace(',','.',trim($row_data[5]))), 6, '.', '').'</TaxAmount>';
        $totals["10"] += trim($row_data[3]) * trim($row_data[5]);
        break;
    case '99':
        $xml_data .= '<TaxPercent>99</TaxPercent>';
        $xml_data .= '<TaxAmount>0</TaxAmount>';
        $totals["99"] += trim($row_data[3]) * trim($row_data[5]);
        break; 
    default:
        if(trim($row_data[7]) == trim($row_data[5])){
          $xml_data .= '<TaxPercent>0</TaxPercent>';
          $xml_data .= '<TaxAmount>0</TaxAmount>';
          $totals["0"] += floatval(trim($row_data[3])) * floatval(trim($row_data[5]));  
        }       
  }

     $xml_data .= '<RowSumType>1</RowSumType>';

  $xml_data .= '</Item>';
  //ak je desctiption dlhsi ako 100, tak sprav dalsie riadky, ale textove
  $start = $start_next;

  while($start<strlen($row_data[1])){
    $xml_data .= '<Item>';
    if ((strlen($row_data[2]) - $start) > 100){
      $next_len = strrpos(substr($row_data[2],$start,100)," ",-1);
      if ($next_len === false || $next_len == 0){
        $next_len = 100;
      }
    } else {
      $next_len = 100;
    }
      $xml_data .= '<Description>'.htmlspecialchars(substr($row_data[2],$start,$next_len)). '</Description>';
      $xml_data .= '<RowType>2</RowType>';
      $xml_data .= '</Item>';
      $start += $next_len;
    }


  $data['mjvat']= 'F';

  $data['result'] = 0;
  $data['xml'] = $xml_data;
  return $data;
}
function faonlinevfa2mrpks_generate()
{

  $sess_id = session_id();
  $target_dir = "tmp_uploads/";
  $nespracovane_fa = array();
  $start_found = 0;


  $xlsx_vydane_fa = $target_dir . $sess_id . '_' . $_FILES['f_xlsx']["name"];

  $destdir = "downloads/" . $sess_id;
  
  if (!is_dir($destdir)) {
    if (!mkdir($destdir, 0755)) {
      die("Nedokazem vytvorit adresar" . $destdir . ", kontaktujte spravcu");
    }
  }
  //vykonaj zmeny v xml a zapis novy subor do $destdir . "processed_".$_FILES['f_xml_fakodb']["name"];

  if (file_exists($xlsx_vydane_fa)) {

    if (($handle = fopen($xlsx_vydane_fa, "r")) !== FALSE) {


    $inputFileType = IOFactory::identify($xlsx_vydane_fa);
    $reader = IOFactory::createReader($inputFileType);    
    
    $reader->setReadDataOnly(true);
    $reader->setLoadAllSheets();
    $spreadsheet = $reader->load($xlsx_vydane_fa);
 
    $sheet_faktury = $spreadsheet->getSheet(0);
    $sheet_polozky = $spreadsheet->getSheet(1);

    $highest_row_faktury = $sheet_faktury->getHighestRow();
    $highest_row_polozky = $sheet_polozky->getHighestRow();

    //ziskame info o fakturach
    $i=6;
    for($i>=6;$i<$highest_row_faktury;$i++)
    {
      $fa_cislo = trim($sheet_faktury->getCell('A'.$i)->getValue());
      if(strlen($fa_cislo)>0){
        $fa_data[$fa_cislo] = $sheet_faktury->toArray()[$i-1];
      }
    } 
    $fa_cislo = "";
    $polozka_data = array();
    $export_data = '<?xml version="1.0" encoding="UTF-8"?><MRPKSData version="2.0" countryCode="SK" currencyCode="EUR"><IssuedInvoices>';
    //idem polozkami na sheet 1
        //pokial A$i je ine ako $fa_Cislo tak
          //ak je nenulove tak uzavri fakturu
          //vytvor hlavicku novej fakruty          
    //spracuj riadok polozky
    $i=6;
    for($i>=6;$i<$highest_row_polozky;$i++)
    {      
      //nacitaj data o polozke
      $polozka_data =  $sheet_polozky->toArray()[$i-1];  
      $fa_cislo_polozka = trim($polozka_data[0]);  
      
      if(strlen($fa_cislo_polozka)>0){

        if($fa_cislo !== $fa_cislo_polozka){

          if(strlen($fa_cislo)>0){
            //uzavri fakturu
            $export_data .= uzavri_fakturu($totals);
          }      
          //vytvor hlavicku novej faktury
          $data = zacni_fakturu($fa_data[$fa_cislo_polozka], $totals);
          if ($data['result'] == -1) {
            $nespracovane_fa[]["hlavicka"] = $fa_data[$fa_cislo];
          } else {
            $export_data .= $data['xml'];        
          }
          $fa_cislo = $fa_cislo_polozka;
        }
        //spracuj polozku
        $data = polozka($polozka_data, $totals);
        $export_data .= $data['xml'];
      }
    }
      $export_data .= uzavri_fakturu($totals); 
      //uzavri data na export
      $export_data .= '</IssuedInvoices></MRPKSData>';

      //validuj a sparsuj xml data
      libxml_use_internal_errors(true);
      $xml = simplexml_load_string($export_data);
      if ($xml !== false) {
          //vystup
        file_put_contents($destdir . "/processed_" . $_FILES['f_xlsx']["name"] . ".xml", $xml->asXML());
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
      die("Error! Could not open uploaded XLSX file:" . $_FILES['f_xlsx']["name"]);
    }
  } else {
    die("Error! Could not find uploaded XLSX file:" . $_FILES['f_xlsx']["name"]);
  }
  //ak je vsetko ok posli spracovany subor klientovi
  return array(0, $destdir . "/processed_" . $_FILES['f_xlsx']["name"] . ".xml");
} 
?>
