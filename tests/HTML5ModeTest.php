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


class HTML5ModeTest extends PHPTAL_TestCase
{
    function testCDATAScript()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<!DOCTYPE html><script><![CDATA[
            if (2 < 5) {
                alert("</foo>");
            }
        ]]></script>');

        $this->assertHTMLEquals('<!DOCTYPE html><script> if (2 < 5) { alert("<\/foo>"); } </script>', $tpl->execute());
    }

    function testCDATAContent()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<!DOCTYPE html><p><![CDATA[<hello>]]></p>');
        $this->assertHTMLEquals('<!DOCTYPE html><p>&lt;hello&gt;</p>', $tpl->execute());
    }

    function testRemovesXHTMLNS()
    {
        $tpl = $this->newPHPTAL()->setOutputMode(PHPTAL::HTML5)->setSource('
        <html     xmlns="http://www.w3.org/1999/xhtml">
            <x:head  xmlns:x="http://www.w3.org/1999/xhtml"/></html>
            ');

        $this->assertHTMLEquals('<html><head></head></html>', $tpl->execute());

    }

    function testDoctype()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><p><![CDATA[<hello>]]></p>');
        $this->assertHTMLEquals('<!DOCTYPE html><p>&lt;hello&gt;</p>', $tpl->execute());
    }

    function testProlog()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<?xml version="1.0"?><!DOCTYPE html><p><![CDATA[<hello>]]></p>');
        $this->assertHTMLEquals('<!DOCTYPE html><p>&lt;hello&gt;</p>', $tpl->execute());
    }

    function testAttr()
    {
        $this->assertEquals('<html url=http://example.com/?test#test foo=" foo" bar=/bar quz="quz/"></html>',
            $this->newPHPTAL()->setOutputMode(PHPTAL::HTML5)->setSource('<html url="http://example.com/?test#test" foo=" foo" bar="/bar" quz="quz/"></html>')->execute());
    }

    function testEmpty()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title tal:content="nonexistant | nothing" />
            <base href="http://example.com/"></base>
            <basefont face="Helvetica" />
            <meta name="test" content=""></meta>
            <link rel="test"></link>
        </head>
        <body>
            <br/>
            <br />
            <br></br>
            <hr/>
            <img src="test"></img>
            <form>
                <textarea />
                <textarea tal:content="\'\'" />
                <textarea tal:content="nonexistant | nothing" />
            </form>
        </body>
        </html>');
        $res = $tpl->execute();
        $this->assertHTMLEquals('<!DOCTYPE html><html>
                <head>
                    <title></title>
                    <base href="http://example.com/">
                    <basefont face=Helvetica>
                    <meta name=test content="">
                    <link rel=test>
                </head>
                <body>
                    <br>
                    <br>
                    <br>
                    <hr>
                    <img src=test>
                    <form>
                        <textarea></textarea>
                        <textarea></textarea>
                        <textarea></textarea>
                    </form>
                </body>
                </html>', $res);
    }


    function testEmptyAll()
    {
        $emptyElements = array(
            'area','base','basefont','br','col',
            'command','embed','frame','hr','img','input','isindex','keygen','link',
            'meta','param','wbr','source','track',
        );
        foreach($emptyElements as $name) {
            $tpl = $this->newPHPTAL();
            $tpl->setOutputMode(PHPTAL::HTML5);
            $tpl->setSource('<'.$name.' id="123">foo</'.$name.'>');
            $res = $tpl->execute();
            $this->assertEquals('<'.$name.' id=123>', $res);
        }
    }

    function testBoolean()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('
        <html xmlns="http://www.w3.org/1999/xhtml">
        <body>
            <input type="checkbox" checked="checked"></input>
            <input type="text" tal:attributes="readonly \'readonly\'"/>
            <input type="radio" tal:attributes="checked php:true; readonly \'readonly\'"/>
            <input type="radio" tal:attributes="checked php:false; readonly bogus | nothing"/>
            <select>
                <option selected="unexpected value"/>
                <option tal:repeat="n php:range(0,5)" tal:attributes="selected repeat/n/odd"/>
            </select>

            <script defer="defer"></script>
            <script tal:attributes="defer number:1"></script>
        </body>
        </html>');
        $res = $tpl->execute();
        $this->assertHTMLEquals('<html>
                <body>
                    <input type=checkbox checked>
                    <input type=text readonly>
                    <input type=radio checked readonly>
                    <input type=radio>
                    <select>
                        <option selected></option>
                        <option></option><option selected></option><option></option><option selected></option><option></option><option selected></option>
                    </select>

                    <script defer></script>
                    <script defer></script>
                </body>
                </html>', $res);
   }

   function testMixedModes()
   {
       $tpl = $this->newPHPTAL();
       $tpl->setOutputMode(PHPTAL::HTML5);
       $tpl->setSource('<input checked="checked"/>');
       $this->assertEquals('<input checked>',$tpl->execute());

       $tpl->setOutputMode(PHPTAL::XHTML);
       $this->assertEquals('<input checked="checked"/>',$tpl->execute());
   }

   private function decodeNumericEntities($str)
   {
       return normalize_html(@preg_replace('/&#x?[a-f0-9]+;/ie','htmlspecialchars(html_entity_decode("\\0"))', $str));
   }

   function testAttributeQuotes()
   {
       $res = $this->newPHPTAL()->setSource('<a test=\'${php:chr(34)}\' tal:attributes="foo php:chr(34)"
       class=\'email
        href="mailto:me"
       \'
       href
       = \'
       &#x20;&#x6d;&#97;i&#108;&#x74;o&#x3a;&#x20;&#37;&#55;0o&#x72;&#110;&#x65;%&#x36;&#x63;&#x25;&#x34;&#x30;&#x70;&#37;6&#102;%7&#x32;&#x6e;e%&#x36;c&#37;2en&#x65;t?
       \'>contact me</a>')->execute();

       $this->assertEquals($this->decodeNumericEntities('<a test="&quot;"
          class="email
           href=&quot;mailto:me&quot;
          "
          href="
           mailto: %70orne%6c%40p%6f%72ne%6c%2enet?
          " foo="&quot;">contact me</a>'),$this->decodeNumericEntities($res));
   }
}

