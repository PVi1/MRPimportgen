<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pscextract
 *
 * @author pvi
 */
class pscextract {   

    private $_string;

    private $_sections = array();

    private $_output = array();

    private $_found = array();

    private $_original_string;

    private $_countries = array (
        'United States',
        'Canada',
        'Mexico',
        'France',
        'Belgium',
        'United Kingdom',
        'Sweden',
        'Denmark',
        'Spain',
        'Australia',
        'Austria',
        'Italy',
        'Netherlands'
    );

    private $_zipcon = array();

    private $ZIPREG = array(
        "United States"=>"^\d{5}([\-]?\d{4})?$",
        "United Kingdom"=>"^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$",
        "Germany"=>"\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b",
        "Canada"=>"^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\s*(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$",
        "France"=>"^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$",
        "Italy"=>"^(V-|I-)?[0-9]{5}$",
        "Australia"=>"^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$",
        "Netherlands"=>"^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$",
        "Spain"=>"^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$",
        "Denmark"=>"^([D-d][K-k])?( |-)?[1-9]{1}[0-9]{3}$",
        "Sweden"=>"^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$",
        "Belgium"=>"^[1-9]{1}[0-9]{3}$",
        "Slovakia"=>"^[1-9]{3}[ ]+[0-9]{2}$",
        "Czech Republic"=>"^[1-9]{3}[ ]+[0-9]{2}$"
    ); // thanks to http://www.pixelenvision.com/1708/zip-postal-code-validation-regex-php-code-for-12-countries/

    public function __construct($string) {

        $this->_output = array (
            "state" => "",
            "city" => "",            
            "zip" => ""           
        );
        $this->_original_string = $string;
        $this->_string = $this->normalize(trim($string));


        // create an array of patterns in order to extract zip code using the country list we already have
        foreach($this->ZIPREG as $country => $pattern) {
            $this->_zipcon[] = $pattern = preg_replace( array("/\^/","/\\$/"),array("",""), $pattern);
        }

        $this->init();
    }

    protected function init() {

        $this->getData(); // get data that can be found without breaking up the string.

       // $this->_sections = array_filter(explode(' ', trim($this->_string)));  // split each section

        if(!empty($this->_sections)) {
            foreach($this->_sections as $i => $d) {
                $d = preg_replace(array("/\s+/", "/\s([?.!])/"),  array(" ","$1"), $d ); 
                $this->_sections[$i] = trim($this->normalize($d));  // normalize strin to have one spacing between each word
            }
        } else {
            $this->_sections[] = $this->_string;    
        }       

        // try to match what's missing with has already been found
        $notFound = $this->getNotFound();
        if(count($notFound)==1 && count($this->_found)>1) {
            $found = $this->getFound();
            foreach($found as $string) {
                $notFound[0] = preg_replace("/$string/i", "", $notFound[0]);
            }
            $this->_output["city"] = $notFound[0];
            $this->_found[] = $this->_output["city"];
            $this->remove($this->_output["city"]);
        }   
    }

    public function getSections() {
        return $this->_sections;
    }   

    protected function normalize($string) {
        $string = preg_replace(array("/\s+/", "/\s([?.!])/"),  array(" ","$1"), trim($string));
        return $string;
    }

    protected function country_from_zip($zip) {
        $found = "";
        foreach($this->ZIPREG as $country => $pattern) {
            if(preg_match ("/".$pattern."/", $zip)) {
                $found = $country;
                break;
            }
        }
        return $found;
    }

    protected function getData() {
        $container = array();
        // extract zip code only when present beside state, or else five digits are meaningless

        if(preg_match ("/[A-Z]{2,}\s*(".implode('|', $this->_zipcon).")/", $this->_string) ){
            preg_match ("/[A-Z]{2,}\s*(".implode('|', $this->_zipcon).")/", $this->_string, $container["state_zip"]);

            $this->_output["state"] = $container["state_zip"][0];
            $this->_output["zip"] = $container["state_zip"][1];
            $this->_found[] = $this->_output["state"] . " ". $this->_output["zip"];
            // remove from string once found
            $this->remove($this->_output["zip"]);   
            $this->remove($this->_output["state"]);

            // check to see if we can find the country just by inputting zip code
            if($this->_output["zip"]!="" ) {
                $country = $this->country_from_zip($this->_output["zip"]);
                $this->_output["country"] = $country;
                $this->_found[] = $this->_output["country"];
                $this->remove($this->_output["country"]);
            }
        } 

        if(preg_match ("/\b([A-Z]{2,})\b/", $this->_string)) {
            preg_match ("/\b([A-Z]{2,})\b/", $this->_string, $container["state"]);  
            $this->_output["state"] = $container["state"][0];
            $this->_found[] = $this->_output['state'];
            $this->remove($this->_output["state"]);
        }

        // if we weren't able to find a country based on the zip code, use the one provided (if provided)
        if($this->_output["country"] == "" && preg_match("/(". implode('|',$this->_countries)  . ")/i", $this->_string) ){
            preg_match ("/(". implode('|',$this->_countries)  . ")/i", $this->_string, $container["country"]);
            $this->_output["country"] = $container["country"][0];
            $this->_found[] = $this->_output['country'];
            $this->remove($this->_output["country"]);
        }   

    }

    /* remove from string in order to make it easier to find missing this */
    protected function remove($string, $case_sensitive = false) {
        $s = ($case_sensitive==false ? "i" : "");
        $this->_string = preg_replace("/".$string."/$s", "", $this->_string);
    }

    public function getNotFound() {
        return array_values(array_filter(array_diff($this->_sections, $this->_found)));
    }

    public function getFound() {
        return $this->_found;   
    }

    /* outputs a readable string with all items found */
    public function toString() {
        $output = $this->getOutput();
        $string = "Original string: [ ".$this->_original_string.' ] ---- New string: [ '. $this->_string. ' ]<br>';
        foreach($output as $type => $data) {
            $string .= "-".$type . ": " . $data. '<br>';    
        }   
        return $string;
    }

    /* return the final output as an array */
    public function getOutput() {
        return $this->_output;  
    }   

}



