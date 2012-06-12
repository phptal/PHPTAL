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


class TalAttributesTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.01.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-attributes.01.html');
        $this->assertEquals($exp, $res);
    }

    function testWithContent()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.02.html');
        $tpl->spanClass = 'dummy';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-attributes.02.html');
        $this->assertEquals($exp, $res);
    }

    function testMultiples()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.03.html');
        $tpl->spanClass = 'dummy';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-attributes.03.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.04.html');
        $tpl->spanClass = 'dummy';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-attributes.04.html');
        $this->assertEquals($exp, $res);
    }

    function testMultipleChains()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.05.html');
        $tpl->spanClass = 'dummy';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-attributes.05.html');
        $this->assertEquals($exp, $res);
    }

    function testEncoding()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.06.html');
        $tpl->href = "http://www.test.com/?foo=bar&buz=biz&<thisissomething";
        $tpl->title = 'bla bla <blabla>';
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-attributes.06.html');
        $this->assertEquals($exp, $res);
    }

    function testZeroValues()
    {
        $tpl = $this->newPHPTAL('input/tal-attributes.07.html');
        $tpl->href1 = 0;
        $tpl->href2 = 0;
        $tpl->href3 = 0;
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-attributes.07.html');
        $this->assertEquals($exp, $res);
    }

    function testEmpty()
    {
        $src = <<<EOT
<span class="&quot;'default" tal:attributes="class nullv | falsev | emptystrv | zerov | default"></span>
EOT;
        $exp = <<<EOT
<span class="0"></span>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src, __FILE__);
        $tpl->nullv = null;
        $tpl->falsev = false;
        $tpl->emptystrv = '';
        $tpl->zerov = 0;
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testSingleQuote()
    {
        $exp = normalize_html_file('output/tal-attributes.08.html');
        $tpl = $this->newPHPTAL('input/tal-attributes.08.html');
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $exp = normalize_html_file('output/tal-attributes.09.html');
        $tpl = $this->newPHPTAL('input/tal-attributes.09.html');
        $tpl->value = "return confirm('hel<lo');";
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testChainedStructure()
    {
        $exp = normalize_html_file('output/tal-attributes.10.html');
        $tpl = $this->newPHPTAL('input/tal-attributes.10.html');
        $tpl->value1 = false;
        $tpl->value2 = "return confirm('hel<lo');";
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testNothingValue()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:attributes="title missing | nothing"></p>');
        $res = $tpl->execute();
        $this->assertEquals($res, '<p></p>');
    }

    function testNULLValue()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:attributes="title missing | php:NULL"></p><p tal:attributes="class \'ok\'; title null:blah"></p>');
        $res = $tpl->execute();
        $this->assertEquals('<p></p><p class="ok"></p>', $res);
    }

    function testNULLValueNoAlternative()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:attributes="title php:NULL"></p>');
        $res = $tpl->execute();
        $this->assertEquals('<p></p>', $res);
    }

    function testNULLValueReversed()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:attributes="title php:true ? NULL : false; class structure php:false ? NULL : \'fo\\\'o\'; style structure php:true ? NULL : false;"></p>');
        $res = $tpl->execute();
        $this->assertEquals('<p class="fo\'o"></p>', $res);
    }

    function testEmptyValue()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:attributes="title missing | \'\'"></p><p tal:attributes="title missing | php:\'\'"></p>');
        $res = $tpl->execute();
        $this->assertEquals('<p title=""></p><p title=""></p>', $res);
    }

    function testSemicolon()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><p tal:content="\'\\\'a;b;;c;;;d\'" tal:attributes="style \'color:red;; font-weight:bold;;;;\'; title php:\'\\\'test;;test;;;;test\'"></p></div>');
        $res = $tpl->execute();
        $this->assertEquals($res, '<div><p style="color:red; font-weight:bold;;" title="&#039;test;test;;test">&#039;a;b;;c;;;d</p></div>');
    }

    function testBoolean()
    {
        $booleanAttrs = array(
            'checked','disabled','autoplay','async','autofocus','controls',
            'default','defer','formnovalidate','hidden','ismap','itemscope',
            'loop','multiple','novalidate','open','pubdate','readonly',
            'required','reversed','scoped','seamless','selected'
        );
        foreach($booleanAttrs as $name) {
            // XHTML
            $tpl = $this->newPHPTAL();
            $tpl->setOutputMode(PHPTAL::XHTML);
            $tpl->setSource('<p '.$name.'="123" tal:attributes="'.$name.' attrval"></p>');
            $tpl->attrval = true;
            $res = $tpl->execute();
            $this->assertEquals('<p '.$name.'="'.$name.'"></p>', $res);
            $tpl->attrval = false;
            $res = $tpl->execute();
            $this->assertEquals('<p></p>', $res);
            // HTML5
            $tpl = $this->newPHPTAL();
            $tpl->setOutputMode(PHPTAL::HTML5);
            $tpl->setSource('<p '.$name.'="123" tal:attributes="'.$name.' attrval"></p>');
            $tpl->attrval = true;
            $res = $tpl->execute();
            $this->assertEquals('<p '.$name.'></p>', $res);
            $tpl->attrval = false;
            $res = $tpl->execute();
            $this->assertEquals('<p></p>', $res);
        }
    }
}


function phptal_tales_null($code, $nothrow)
{
    return 'NULL';
}
