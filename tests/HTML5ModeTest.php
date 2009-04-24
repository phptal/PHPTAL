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

        $this->assertEquals(trim_string('<!DOCTYPE html><script> if (2 < 5) { alert("<\/foo>"); } </script>'),trim_string($tpl->execute()));
    }

    function testCDATAContent()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<!DOCTYPE html><p><![CDATA[<hello>]]></p>');
        $this->assertEquals(trim_string('<!DOCTYPE html><p>&lt;hello&gt;</p>'),trim_string($tpl->execute()));
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
        $res = trim_string($res);
        $exp = trim_string('<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml">
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
                </html>');
        $this->assertEquals($exp, $res);
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
        $res = trim_string($res);
        $exp = trim_string('<html xmlns="http://www.w3.org/1999/xhtml">
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
                </html>');
        $this->assertEquals($exp, $res);
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
}

