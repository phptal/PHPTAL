<?php

require_once 'PHPTAL/TranslationService.php';

class DummyTranslator implements PHPTAL_TranslationService
{
    public $vars = array();
    
    public function setLanguage($langCode){
    }
    
    public function setDomain($domain){
    }
    
    public function setVar($key, $value){ 
        $this->vars[$key] = $value; 
    }
    
    public function translate($key){ 
        while (preg_match('/\$\{(.*?)\}/sm', $key, $m)){
            list($src, $var) = $m;
            $key = str_replace($src, $this->vars[$var], $key);
        }
        return $key; 
    }
}
?>
