<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */



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
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/gettext.01.html');
        $this->assertEquals($exp, $res);
    }

    function testLang()
    {
        $gettext = $this->getTextTranslator();
        try {
            $gettext->setLanguage('fr_FR', 'fr_FR@euro', 'fr_FR.utf8');
        } catch(PHPTAL_ConfigurationException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        $gettext->addDomain('test');
        $gettext->useDomain('test');

        $tpl = $this->newPHPTAL('input/gettext.02.html');
        $tpl->setTranslator($gettext);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/gettext.02.html');
        $this->assertEquals($exp, $res);
    }

    function testInterpol()
    {
        $gettext = $this->getTextTranslator();
        try {
            $gettext->setLanguage('fr_FR', 'fr_FR@euro', 'fr_FR.utf8');
        } catch(PHPTAL_ConfigurationException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        $gettext->setEncoding('UTF-8');
        $gettext->addDomain('test');
        $gettext->useDomain('test');

        $tpl = $this->newPHPTAL('input/gettext.03.html');
        $tpl->setTranslator($gettext);
        $tpl->login = 'john';
        $tpl->lastCxDate = '2004-12-25';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/gettext.03.html');
        $this->assertEquals($exp, $res);
    }

    function testDomainChange()
    {
        $gettext = $this->getTextTranslator();
        $gettext->setEncoding('UTF-8');
        try {
            $gettext->setLanguage('fr_FR', 'fr_FR@euro', 'fr_FR.utf8');
        } catch(PHPTAL_ConfigurationException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        $gettext->addDomain('test');
        $gettext->addDomain('test2');
        $gettext->useDomain('test');

        $tpl = $this->newPHPTAL('input/gettext.04.html');
        $tpl->setEncoding('UTF-8');
        $tpl->setTranslator($gettext);
        $tpl->login = 'john';
        $tpl->lastCxDate = '2004-12-25';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/gettext.04.html');
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
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/gettext.05.html');
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
        $res = normalize_html($res);
        $exp = normalize_html_file('output/gettext.06.html');
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
        $res = normalize_html($res);
        $exp = normalize_html('<root>
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
        $res = normalize_html($res);
        $exp = normalize_html_file('output/gettext.07.html');
        $this->assertEquals($exp, $res);
    }
}

