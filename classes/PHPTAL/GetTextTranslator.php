<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

require_once PHPTAL_DIR.'PHPTAL/TranslationService.php';

/**
 * PHPTAL_TranslationService gettext implementation.
 *
 * Because gettext is the most common translation library in use, this
 * implementation is shipped with the PHPTAL library.
 *
 * Please refer to the PHPTAL documentation for usage examples.
 * 
 * @package phptal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_GetTextTranslator implements PHPTAL_TranslationService
{
    public function __construct()
    {
	    if (!function_exists('gettext')) throw new PHPTAL_ConfigurationException("Gettext not installed");
	    $this->useDomain("messages"); // PHP bug #21965
    }

    private $_vars = array();
    private $_currentDomain;
    private $_encoding = 'UTF-8';
    private $_canonicalize = false;

    public function setEncoding($enc)
    {
        $this->_encoding = $enc;
    }
    
    /**
     * if true, all non-ASCII characters in keys will be converted to C<xxx> form. This impacts performance.
     * by default keys will be passed to gettext unmodified.
     */
    public function setCanonicalize($bool)
    {
        $this->_canonicalize = $bool;
    }
    
    public function setLanguage()
    {
        $langs = func_get_args();
        foreach ($langs as $langCode){
            putenv("LANG=$langCode");
            putenv("LC_ALL=$langCode");
            putenv("LANGUAGE=$langCode");
            if (setlocale(LC_ALL, $langCode)) {
                return;
            }
        }

        throw new PHPTAL_ConfigurationException('Language(s) code(s) "'.implode(', ', $langs).'" not supported by your system');
    }
    
    /**
     * encoding must be set before calling addDomain
     */
    public function addDomain($domain, $path='./locale/')
    {
        bindtextdomain($domain, $path);
        if ($this->_encoding){
            bind_textdomain_codeset($domain, $this->_encoding);
        }
        $this->useDomain($domain);
    }
    
    public function useDomain($domain)
    {
        $old = $this->_currentDomain;
        $this->_currentDomain = $domain;
        textdomain($domain);
        return $old;
    }
    
    public function setVar($key, $value)
    {
        $this->_vars[$key] = $value;
    }
    
    public function translate($key, $htmlencode=true)
    {
        if ($this->_canonicalize) $key = self::_canonicalizeKey($key);
        
        $value = gettext($key);
        
        if ($htmlencode){
            $value = @htmlspecialchars($value, ENT_QUOTES, $this->_encoding); // silence unsupported encoding error for ISO-8859-x, which doesn't matter.
        }
        while (preg_match('/\${(.*?)\}/sm', $value, $m)){
            list($src,$var) = $m;
            if (!array_key_exists($var, $this->_vars)){
                $err = sprintf('Interpolation error, var "%s" not set', $var);
                throw new PHPTAL_VariableNotFoundException($err);
            }
            $value = str_replace($src, $this->_vars[$var], $value);
        }
        return $value;
    }

    static function _canonicalizeKey($key_)
    {
        $result = "";
        $key_ = trim($key_);
        $key_ = str_replace("\n", "", $key_);
        $key_ = str_replace("\r", "", $key_);
        for ($i = 0; $i<strlen($key_); $i++){
            $c = $key_[$i];
            $o = ord($c);
            if ($o < 5 || $o > 127){
                $result .= 'C<'.$o.'>';
            }
            else {
                $result .= $c;
            }
        }
        return $result;
    }
}

