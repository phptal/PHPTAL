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

require_once PHPTAL_DIR.'PHPTAL/GetTextTranslator.php';

class GetTextTest extends PHPTAL_TestCase
{
    private function getTextTranslator()
    {
        try
        {
            return new PHPTAL_GetTextTranslator();
        }
        catch(PHPTAL_Exception $e)
        {            
            $this->markTestSkipped($e->getMessage());
        }
    }
    
    
    function testSimple()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('en_GB', 'en_GB.utf8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');
        
        $tpl = $this->newPHPTAL('input/gettext.01.html');
        $tpl->setTranslator($gettext);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/gettext.01.html');
        $this->assertEquals($exp, $res);
    }

    function testLang()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('fr_FR', 'fr_FR@euro', 'fr_FR.utf8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');
        
        $tpl = $this->newPHPTAL('input/gettext.02.html');
        $tpl->setTranslator($gettext);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/gettext.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testInterpol()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('fr_FR', 'fr_FR@euro', 'fr_FR.utf8');
        $gettext->setEncoding('UTF-8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');
        
        $tpl = $this->newPHPTAL('input/gettext.03.html');
        $tpl->setTranslator($gettext);
        $tpl->login = 'john';
        $tpl->lastCxDate = '2004-12-25';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/gettext.03.html');
        $this->assertEquals($exp, $res);        
    }

    function testDomainChange()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setEncoding('UTF-8');
        $gettext->setLanguage('fr_FR', 'fr_FR@euro', 'fr_FR.utf8');
        $gettext->addDomain('test');
        $gettext->addDomain('test2');
        $gettext->useDomain('test');
        
        $tpl = $this->newPHPTAL('input/gettext.04.html');
        $tpl->setEncoding('UTF-8');
        $tpl->setTranslator($gettext);
        $tpl->login = 'john';
        $tpl->lastCxDate = '2004-12-25';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/gettext.04.html');
        $this->assertEquals($exp, $res);                
    }

    function testSpaces()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('en_GB', 'en_GB.utf8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');
        
        $tpl = $this->newPHPTAL('input/gettext.05.html');
        $tpl->login = 'john smith';
        $tpl->setTranslator($gettext);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/gettext.05.html');
        $this->assertEquals($exp, $res);
    }

    function testAccentuateKey()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('en_GB', 'en_GB.utf8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');
        $gettext->setCanonicalize(true);

        $tpl = $this->newPHPTAL('input/gettext.06.html');
        $tpl->setTranslator($gettext);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/gettext.06.html');
        $this->assertEquals($exp, $res);
    }
    
    function testAccentuateKeyNonCanonical()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('en_GB', 'en_GB.utf8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');

        $tpl = $this->newPHPTAL('input/gettext.06.html');
        $tpl->setTranslator($gettext);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_string('<root>
  <span>Not accentuated</span>
  <span>Accentuated key without canonicalization</span>
  <span>Accentuated key without canonicalization</span>
</root>
');
        $this->assertEquals($exp, $res);
    }

    function testQuote()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setLanguage('en_GB', 'en_GB.utf8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');

        $tpl = $this->newPHPTAL('input/gettext.07.html');
        $tpl->setTranslator($gettext);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/gettext.07.html');
        $this->assertEquals($exp, $res);
    }
}

?>
