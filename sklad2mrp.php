<?php

//session_start();

function build_archive($sess_id) {

    $destdir = "downloads/" . $sess_id . '/';

    $zip = new ZipArchive();
    $ret = $zip->open($destdir . 'mrp_import.zip', ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE);
    if ($ret !== TRUE) {
        die('Failed with code ' . $ret);
    } else {
        $directory = realpath($destdir);
        $options = array('add_path' => '/', 'remove_path' => $directory);
        $zip->addPattern('/\.(?:txt)$/', $directory, $options);
        $zip->close();
    }  
    return $destdir . "mrp_import.zip";
}

function sklad_generate_txt() {

    $sess_id = session_id();
    $target_dir = "tmp_uploads/";
    $nespracovane_fa = array();
    $nespracovane_fa_pol = array();
    $typ_polozky = "S";


// open in read-only mode

    $db_adresy = dbase_open($target_dir . $sess_id . '_' . 'adresy.DBF', 0) or die("Error! Could not open dbase adresy.DBF database file.");
    $db_vydane_fa = dbase_open($target_dir . $sess_id . '_' . 'fakodb.DBF', 0) or die("Error! Could not open dbase fakodb.DBF database file.");
    $db_vydane_fa_pol = dbase_open($target_dir . $sess_id . '_' . 'fotext.DBF', 0) or die("Error! Could not open dbase fotext.DBF database file.");

    $destdir = "downloads/" . $sess_id;

    if (!is_dir($destdir)) {
        if (!mkdir($destdir, 0755)) {
            die("Nedokazem vytvorit adresar" . $destdir . ", kontaktujte spravcu");
        }
    }

    //najprv polozky a znacit do pola aka bola dph per faktura.
    //az potom ist na faktury
    if ($db_adresy) {
// $adresy_mrp = array();

        $record_numbers = dbase_numrecords($db_adresy);

        for ($i = 1; $i <= $record_numbers; $i++) {
            $row = dbase_get_record_with_names($db_adresy, $i);
            $adresy_mrp[$i]["nazov"] = str_pad(trim($row['ODBER1']), 50);
            $adresy_mrp[$i]["ICO"] = str_pad(trim($row['ICO']), 12);
            $adresy_mrp[$i]["meno"] = str_pad(trim($row['ODBER2']), 30);
            $adresy_mrp[$i]["ulica"] = str_pad(trim($row['ODBER3']), 30);

            $psc = " ";
            $mesto = " ";

            $Extract = explode(' ', trim($row['ODBER4']));

            if (count($Extract) > 2 && strlen($Extract[0]) < 4) {
                $psc = array_shift($Extract);
                $psc .= " " . array_shift($Extract);
                $mesto = implode(" ", $Extract);
            } else if (strlen($Extract[0] > 0)) {
                $psc = array_shift($Extract);
                $mesto = implode(" ", $Extract);
            }

            $adresy_mrp[$i]["mesto"] = str_pad($mesto, 30);
            $adresy_mrp[$i]["stat"] = str_pad(" ", 30);
            $adresy_mrp[$i]["pozn"] = str_pad(" ", 30);
            $adresy_mrp[$i]["psc"] = str_pad($psc, 15);
// echo "PSC: {$psc} a mesto {$mesto}\n";
            $adresy_mrp[$i]["DIC"] = str_pad(trim($row['DIC']), 17);
            $adresy_mrp[$i]["tel"] = str_pad(trim($row['TELEFON']), 30);
            $adresy_mrp[$i]["tel2"] = str_pad(" ", 30);
            $adresy_mrp[$i]["tel3"] = str_pad(" ", 30);
            $adresy_mrp[$i]["fax"] = str_pad(trim($row['FAX']), 30);
            $adresy_mrp[$i]["email"] = str_pad(trim($row['EMAIL']), 50);
            $adresy_mrp[$i]["banka"] = str_pad(trim($row['ODBERBAN']), 30);
            $adresy_mrp[$i]["ucet"] = str_pad(trim($row['ODBERUC']), 18);
            $adresy_mrp[$i]["bankakod"] = str_pad(trim($row['KODBANKY']), 12);
            if (trim($row['PRAVSTAT']) == 'F') {
                $adresy_mrp[$i]["fyzprav"] = 'T';
            } else {
                $adresy_mrp[$i]["fyzprav"] = 'F';
            }
            $adresy_mrp[$i]["nazov2"] = str_pad(" ", 50);
            $adresy_mrp[$i]["datumzaradenia"] = str_pad(" ", 10);
            $adresy_mrp[$i]["cislodu"] = str_pad(trim($row['DANURAD']), 5);
            $adresy_mrp[$i]["iban"] = str_pad(" ", 34);
            $adresy_mrp[$i]["icdph"] = str_pad(" ", 14);
            $adresy_mrp[$i]["splatnost"] = str_pad(intval(trim($row['SPLATNOST'])), 3);
            $adresy_mrp[$i]["kodstatu"] = str_pad(" ", 2);
            $adresy_mrp[$i]["pozn2"] = str_pad(" ", 30);
            $adresy_mrp[$i]["swift"] = str_pad(" ", 11);
            $adresy_mrp[$i]["ean"] = str_pad(trim($row['EANKOD']), 17);
        }
//zapis do suboru
        $fadresy = fopen($destdir . "/adres.txt", "w");
        foreach ($adresy_mrp as $adresa) {
            fwrite($fadresy, implode("", $adresa));
            fwrite($fadresy, "\n");
        }
        fclose($fadresy);
        dbase_close($db_adresy);

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


    if ($db_vydane_fa_pol) {

        $dph_rozpis = array();
        $record_numbers = dbase_numrecords($db_vydane_fa_pol);

        for ($i = 1; $i <= $record_numbers; $i++) {
            $row = dbase_get_record_with_names($db_vydane_fa_pol, $i);
            $faktury_mrp_pol[$i]["cislo_fa"] = str_pad(trim($row['KCISFAK']), 10);
            $faktury_mrp_pol[$i]["text"] = str_pad(trim($row['TEXT']), 50);
            $faktury_mrp_pol[$i]["mj"] = str_pad(trim($row['JED']), 3);
            $faktury_mrp_pol[$i]["pocet"] = str_pad(number_format(trim($row['MNO']), 3, ",", ""), 10, " ", STR_PAD_LEFT);
            $faktury_mrp_pol[$i]["cena_pol"] = str_pad(number_format(trim($row['CENPOL']), 4, ",", ""), 12, " ", STR_PAD_LEFT);
            $faktury_mrp_pol[$i]["dph"] = str_pad(trim($row['DAN']), 2);
            $hodnota_dph = trim($row['CENPOL']) * trim($row['DAN']) / 100;
            $faktury_mrp_pol[$i]["dph_value"] = str_pad(number_format($hodnota_dph, 4, ",", ""), 12, " ", STR_PAD_LEFT);
            $faktury_mrp_pol[$i]["zlava"] = str_pad("0,00", 6, " ", STR_PAD_LEFT);
            $faktury_mrp_pol[$i]["index"] = str_pad($i, 3, " ", STR_PAD_LEFT);
            $faktury_mrp_pol[$i]["karta"] = str_pad(number_format(trim($row['KARTA']), 2, ",", ""), 10, " ", STR_PAD_LEFT);
            $faktury_mrp_pol[$i]["hmotnost"] = str_pad("0,000", 10, " ", STR_PAD_LEFT);
            if (trim($row['DAN']) == 0) {
//neda sa to rozlisit, robime by default
                $faktury_mrp_pol[$i]["typ"] = str_pad($typ_polozky, 2);
            } else {
                $faktury_mrp_pol[$i]["typ"] = str_pad(" ", 2);
            }
            $dph_rozpis[trim($row['KCISFAK'])][trim($row['DAN'])]["zaklad"] += trim($row['CENPOL']);
            $dph_rozpis[trim($row['KCISFAK'])][trim($row['DAN'])]["dph"] += $hodnota_dph;
//            echo "\n". trim($row['KCISFAK'])." ".trim($row['DAN'])." zaklad:" .trim($row['CENPOL']);
//            echo "\n". trim($row['KCISFAK'])." ".trim($row['DAN'])."dph:". $hodnota_dph;
        }
        $ffaktury = fopen($destdir . "/FvPolImp.txt", "w");
        foreach ($faktury_mrp_pol as $faktura_pol) {
            fwrite($ffaktury, implode("", $faktura_pol));
            fwrite($ffaktury, "\n");
        }
        fclose($ffaktury);
        dbase_close($db_vydane_fa_pol);
    }

    if ($db_vydane_fa) {

        $record_numbers = dbase_numrecords($db_vydane_fa);

        for ($i = 1; $i <= $record_numbers; $i++) {
            $row = dbase_get_record_with_names($db_vydane_fa, $i);
            $faktury_mrp[$i]["druh"] = str_pad(trim($row['DRUH']), 1);
            $faktury_mrp[$i]["cislo"] = str_pad(trim($row['CISFAK']), 10);
            $faktury_mrp[$i]["odb_ico"] = str_pad(trim($row['ICO']), 12);
            if (trim($row['DPH']) <> 0) {
                $druh_dph = 10;
            } else {
                $druh_dph = 19;
            }
            $faktury_mrp[$i]["typy_dph"] = str_pad($druh_dph, 2);
            //sem pridat rozhodovacie pravidlo kedy aku dph pocitat
            //toto je zakl0
            $faktury_mrp[$i]["zaklad_bez_dph"] = str_pad(number_format($dph_rozpis[trim($row['CISFAK'])]["0"]["zaklad"], 2, ",", ""), 12, " ", STR_PAD_LEFT);
            //zakl1
            $faktury_mrp[$i]["zaklad_nizke_dph"] = str_pad(number_format($dph_rozpis[trim($row['CISFAK'])]["10"]["zaklad"], 2, ",", ""), 12, " ", STR_PAD_LEFT);
            //zakl2
            $faktury_mrp[$i]["zaklad_zakladne_dph"] = str_pad(number_format($dph_rozpis[trim($row['CISFAK'])]["20"]["zaklad"], 2, ",", ""), 12, " ", STR_PAD_LEFT);

            $faktury_mrp[$i]["zaklad_mimo_dph"] = str_pad(number_format($dph_rozpis[trim($row['CISFAK'])]["0"]["zaklad"], 2, ",", ""), 12, " ", STR_PAD_LEFT);

            $faktury_mrp[$i]["suma_nizke_dph"] = str_pad(number_format($dph_rozpis[trim($row['CISFAK'])]["10"]["dph"], 2, ",", ""), 12, " ", STR_PAD_LEFT);

            $faktury_mrp[$i]["suma_zaklad_dph"] = str_pad(number_format($dph_rozpis[trim($row['CISFAK'])]["20"]["dph"], 2, ",", ""), 12, " ", STR_PAD_LEFT);

            $faktury_mrp[$i]["zaklad_nizke_dph_neu"] = str_pad("0,00", 12, " ", STR_PAD_LEFT);
            $faktury_mrp[$i]["zaklad_zakladne_dph_neu"] = str_pad("0,00", 12, " ", STR_PAD_LEFT);

            $faktury_mrp[$i]["suma_nizke_dph_neu"] = str_pad("0,00", 12, " ", STR_PAD_LEFT);
            $faktury_mrp[$i]["suma_zaklad_dph_neu"] = str_pad("0,00", 12, " ", STR_PAD_LEFT);
            $faktury_mrp[$i]["suma_uhrad"] = str_pad("0,00", 12, " ", STR_PAD_LEFT);
            $faktury_mrp[$i]["cislo_dod_list"] = str_pad(" ", 10);
//konverzia datumu z rmd na d.m.r
            $dat_vyst = date_create_from_format('Ymd', $row['DATODESL']);
            if ($dat_vyst) {
                $faktury_mrp[$i]["datum_vystavenia"] = str_pad(date_format($dat_vyst, 'd.m.Y'), 10);
            } else {
//poznac cislo FA ktora sa nesparsovala
                $nespracovane_fa["cislo"]["cislo"] = $faktury_mrp[$i]["cislo"];
                $nespracovane_fa["cislo"]["dovod"] = "Nespravny datum vystavenia - " . $row['DATODESL'];
                unset($faktury_mrp[$i]);
            }
            $dat_zdanpov = date_create_from_format('Ymd', $row['DATPOVFAK']);
            if ($dat_zdanpov) {
                $faktury_mrp[$i]["datum_danpov"] = str_pad(date_format($dat_zdanpov, 'd.m.Y'), 10);
            } else {
//poznac cislo FA ktora sa nesparsovala
                $nespracovane_fa["cislo"]["cislo"] = $faktury_mrp[$i]["cislo"];
                $nespracovane_fa["cislo"]["dovod"] = "Nespravny datum danovej povinnosti - " . $row['DATPOVFAK'];
                unset($faktury_mrp[$i]);
            }
            $dat_splat = date_create_from_format('Ymd', $row['DATSPL']);
            if ($dat_splat) {
                $faktury_mrp[$i]["datum_splat"] = str_pad(date_format($dat_splat, 'd.m.Y'), 10);
            } else {
//poznac cislo FA ktora sa nesparsovala
                $nespracovane_fa["cislo"]["cislo"] = $faktury_mrp[$i]["cislo"];
                $nespracovane_fa["cislo"]["dovod"] = "Nespravny datum splatnosti - " . $row['DATSPL'];
                unset($faktury_mrp[$i]);
            }
            $faktury_mrp[$i]["var_symbol"] = str_pad(trim($row['CISFAK']), 10);
            $faktury_mrp[$i]["konst_symbol"] = str_pad(trim($row['KONSYM']), 8);
            $faktury_mrp[$i]["stredisko"] = str_pad(trim($row['STREDISKO']), 6);
            $faktury_mrp[$i]["forma_uhr"] = str_pad(trim($row['UHRADA']), 10);
            $faktury_mrp[$i]["sposob_dopr"] = str_pad(trim($row['DOPRAVA']), 10);
            $faktury_mrp[$i]["obj_cislo"] = str_pad(" ", 20);
            $dat_obj = date_create_from_format('Ymd', $row['DATOBJ']);
            if ($dat_obj) {
                $faktury_mrp[$i]["datum_obj"] = str_pad(date_format($dat_obj, 'd.m.Y'), 10);
            } else {
                //tento udaj nieje kriticky
                $faktury_mrp[$i]["datum_obj"] = str_pad(" ", 10);
//poznac cislo FA ktora sa nesparsovala
//                $nespracovane_fa[]["cislo"] = $faktury_mrp[$i]["cislo"];
//                $nespracovane_fa[]["dovod"] = "Nespravny datum objednavky - ". $row['DATOBJ'];
//                unset($faktury_mrp[$i]);
            }
            $faktury_mrp[$i]["prikaz_uhr"] = "0";
            $faktury_mrp[$i]["mena"] = str_pad(trim($row['MENA']), 3);
            if (is_numeric($row['KURZ'] > 0)) {
                $faktury_mrp[$i]["kurz_zahr"] = str_pad(trim($row['KURZ']), 6, " ", STR_PAD_LEFT);
            } else {
                $faktury_mrp[$i]["kurz_zahr"] = str_pad(" ", 6);
            }
            $faktury_mrp[$i]["kurz_dom"] = str_pad(" ", 11);
            $faktury_mrp[$i]["cvv"] = str_pad("0", 3);
            $faktury_mrp[$i]["cislo_plat_karty"] = str_pad("", 20);
            $faktury_mrp[$i]["typ_dokl"] = str_pad("", 2);
            $faktury_mrp[$i]["cislo_predf"] = str_pad(trim($row['PROFORMA']), 10);
            $faktury_mrp[$i]["cislo_zak"] = str_pad(" ", 15);
            $faktury_mrp[$i]["suma_zahr"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zauctovane"] = "F";
            $faktury_mrp[$i]["ico_prij"] = str_pad(trim($row['ICOPRIJ']), 12);
            $faktury_mrp[$i]["id_fak"] = str_pad("0", 10);
            $faktury_mrp[$i]["suma_uhrad_zahr_mena"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["hmotnost"] = str_pad("0,000", 10);
            $dat_dod = date_create_from_format('Ymd', $row['DATPOVFAK']);
            if ($dat_dod) {
                $faktury_mrp[$i]["dat_dodania"] = str_pad(date_format($dat_dod, 'd.m.Y'), 10);
            } else {
//poznac cislo FA ktora sa nesparsovala
                $nespracovane_fa["cislo"]["cislo"] = $faktury_mrp[$i]["cislo"];
                $nespracovane_fa["cislo"]["dovod"] = "Nespravny datum dodania - " . $row['DATPOVFAK'];
                unset($faktury_mrp[$i]);
            }
            $faktury_mrp[$i]["text"] = str_pad(" ", 30);
            $faktury_mrp[$i]["poznamka"] = str_pad(" ", 30);
//blbosti v zahranicnej mene
            $faktury_mrp[$i]["zahr_mena_dph_blbosti1"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti2"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti3"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti4"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti5"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti6"] = str_pad("0,00", 12);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti7"] = str_pad("0", 6);
            $faktury_mrp[$i]["zahr_mena_dph_blbosti8"] = str_pad("0,0000", 11);
            $faktury_mrp[$i]["recykl"] = str_pad("0,00", 9);
            $faktury_mrp[$i]["pocet_des_pol"] = "2";
            if (trim($row['DPH']) <> 0) {
                $faktury_mrp[$i]["ciast_s_dph"] = "F";
            } else {
                $faktury_mrp[$i]["ciast_s_dph"] = "T";
            }
            $faktury_mrp[$i]["prepoc_pol"] = "T";
            $faktury_mrp[$i]["spec_symb"] = str_pad(" ", 93);
        }

//zapis do suboru
        $ffaktury = fopen($destdir . "/FvImp.txt", "w");
        foreach ($faktury_mrp as $faktura) {
            fwrite($ffaktury, implode("", $faktura));
            fwrite($ffaktury, "\n");
        }
        fclose($ffaktury);
        dbase_close($db_vydane_fa);
    }

    unset($dph_rozpis);
//zapis nespracovane faktury do suboru
    if (count($nespracovane_fa) > 0) {
        $ffaktury = fopen($destdir . "/FNespracovane.txt", "w");
        foreach ($nespracovane_fa as $nesp_faktura) {
            fwrite($ffaktury, "Cislo FA: " . $nesp_faktura["cislo"] . " Dovod: " . $nesp_faktura["dovod"] . "\n");
        }
        fclose($ffaktury);
        $cesta_flag = build_archive($sess_id);
        return array(1, $cesta_flag);
    }

//ak je vsetko ok
    $cesta_flag = build_archive($sess_id);
    return array(0, $cesta_flag);
}

?>
