<?php
include_once 'pscextract.php';

function sklad_generate_txt(){
    
     $target_dir = "tmp_uploads/";
// open in read-only mode
    
$db_adresy = dbase_open($target_dir.'adresy.DBF', 0) or die("Error! Could not open dbase database file.");
$db2 = dbase_open($target_dir.'fakodb.DBF', 0) or die("Error! Could not open dbase database file.");
$db3 = dbase_open($target_dir.'fotext.DBF', 0) or die("Error! Could not open dbase database file.");

//adresy
echo '<pre>';

if ($db_adresy) {
   // $adresy_mrp = array();
    
    $record_numbers = dbase_numrecords($db_adresy);
    
     for ($i = 1; $i <= $record_numbers; $i++) {
      $row = dbase_get_record_with_names($db_adresy, $i);
      $adresy_mrp[$i]["nazov"]= str_pad(trim($row['ODBER1']),50);
      $adresy_mrp[$i]["ICO"]= str_pad(trim($row['ICO']),12);
      $adresy_mrp[$i]["meno"]= str_pad(trim($row['ODBER2']),30);
      $adresy_mrp[$i]["ulica"]= str_pad(trim($row['ODBER3']),30);
      
      $psc=" ";
      $mesto=" ";
      
      $Extract = explode(' ',trim($row['ODBER4']));
      
      if(count($Extract)>2 && strlen($Extract[0])<4)    {
        $psc = array_shift($Extract);
        $psc.=" ".array_shift($Extract);
        $mesto = implode(" ", $Extract);
      }
      else if(strlen($Extract[0]>0)){
        $psc = array_shift($Extract);
        $mesto = implode(" ", $Extract);
      }     
    
      $adresy_mrp[$i]["mesto"]= str_pad($mesto,30);
      $adresy_mrp[$i]["stat"]= str_pad(" ",30);
      $adresy_mrp[$i]["pozn"]= str_pad(" ",30);
      $adresy_mrp[$i]["psc"]= str_pad($psc,15);      
     // echo "PSC: {$psc} a mesto {$mesto}\n";
      $adresy_mrp[$i]["DIC"]= str_pad(trim($row['DIC']),17);
      $adresy_mrp[$i]["tel"]= str_pad(trim($row['TELEFON']),30);
      $adresy_mrp[$i]["tel2"]= str_pad(" ",30);
      $adresy_mrp[$i]["tel3"]= str_pad(" ",30);
      $adresy_mrp[$i]["fax"]= str_pad(trim($row['FAX']),30);
      $adresy_mrp[$i]["email"]= str_pad(trim($row['EMAIL']),50);
      $adresy_mrp[$i]["banka"]= str_pad(trim($row['ODBERBAN']),30);
      $adresy_mrp[$i]["ucet"]= str_pad(trim($row['ODBERUC']),18);
      $adresy_mrp[$i]["bankakod"]= str_pad(trim($row['KODBANKY']),12);
      if(trim($row['PRAVSTAT'])=='F'){
        $adresy_mrp[$i]["fyzprav"]= 'T';
      }
      else {
        $adresy_mrp[$i]["fyzprav"]= 'F';
      }
      $adresy_mrp[$i]["nazov2"]= str_pad(" ",50);
      $adresy_mrp[$i]["datumzaradenia"]= str_pad(" ",10);
      $adresy_mrp[$i]["cislodu"]= str_pad(trim($row['DANURAD']),5);
      $adresy_mrp[$i]["iban"]= str_pad(" ",34);
      $adresy_mrp[$i]["icdph"]= str_pad(" ",14);
      $adresy_mrp[$i]["splatnost"]= str_pad(intval(trim($row['SPLATNOST'])),3);
      $adresy_mrp[$i]["kodstatu"]= str_pad(" ",2);
      $adresy_mrp[$i]["pozn2"]= str_pad(" ",30);
      $adresy_mrp[$i]["swift"]= str_pad(" ",11);
      $adresy_mrp[$i]["ean"]= str_pad(trim($row['EANKOD']),17);
                
  }
  //zapis do suboru
  $destdir = "downloads/";
  $fadresy = fopen($destdir."adresy.txt", "w");
  foreach ($adresy_mrp as $adresa){    
      fwrite($fadresy,implode("", $adresa));      
      fwrite($fadresy,"\n");
  }  
  fclose($fadresy);
  
  //posli userovi
  
 //header('Content-Type: text/plain');         # its a text file
 //header('Content-Disposition: attachment');  # hit to trigger external mechanisms instead of inbuilt
 // readfile($filename);
  //
  // read some data ..
// Get column information
//    echo "<html><body>adresy<br><pre>";
//$column_info = dbase_get_header_info($db1);
//nl2br(print_r($column_info));
//
//// Display information
//echo "<br>faktury<br>";
//$column_info = dbase_get_header_info($db2);
//nl2br(print_r($column_info));
//echo "<br>polozky<br>";
//$column_info = dbase_get_header_info($db3);
//nl2br(print_r($column_info));

//
// $record_numbers = dbase_numrecords($db);
//  for ($i = 1; $i <= $record_numbers; $i++) {
//      $row = dbase_get_record_with_names($db, $i);
//        print_r($row);
//    }

  

  
}

dbase_close($db_adresy);
dbase_close($db2);
dbase_close($db3);

}
?>