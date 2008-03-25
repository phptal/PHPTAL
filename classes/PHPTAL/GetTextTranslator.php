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
	    if (!function_exists('gettext')) throw new PHPTAL_Exception("Gettext not installed");
    }

    public function setEncoding($enc)
    {
        $this->_encoding = $enc;
    }
    
    public function setLanguage()
    {
        $langs = func_get_args();
        $found = false;
        foreach ($langs as $langCode){
            putenv("LANG=$langCode");
            putenv("LC_ALL=$langCode");
            putenv("LANGUAGE=$langCode");
            $found = setlocale(LC_ALL, $langCode);
            if ($found) {
                break;
            }
        }
        if (!$found){
            $err = 'Language(s) code(s) "%s" not supported by your system';
            $err = sprintf($err, join(',', $langs));
            throw new PHPTAL_Exception($err);
        }
    }
    
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
        $value = gettext($key);
        if ($htmlencode){
            $value = htmlspecialchars($value, ENT_QUOTES, $this->_encoding);
        }
        while (preg_match('/\${(.*?)\}/sm', $value, $m)){
            list($src,$var) = $m;
            if (!array_key_exists($var, $this->_vars)){
                $err = sprintf('Interpolation error, var "%s" not set', $var);
                throw new PHPTAL_Exception($err);
            }
            $value = str_replace($src, $this->_vars[$var], $value);
        }
        return $value;
    }

    private $_vars = array();
    private $_currentDomain = null;
    private $_encoding = 'UTF-8';
}

