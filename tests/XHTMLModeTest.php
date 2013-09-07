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


class XHTMLModeTest extends PHPTAL_TestCase
{
    function testEmpty()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<html xmlns="http://www.w3.org/1999/xhtml">
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
        $res = normalize_html($res);
        $exp = normalize_html('<html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <title></title>
                    <base href="http://example.com/" />
                    <basefont face="Helvetica" />
                    <meta name="test" content="" />
                    <link rel="test" />
                </head>
                <body>
                    <br />
                    <br />
                    <br />
                    <hr />
                    <img src="test" />
                    <form>
                        <textarea></textarea>
                        <textarea></textarea>
                        <textarea></textarea>
                    </form>
                </body>
                </html>');
        $this->assertEquals($exp, $res);
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
            $tpl->setOutputMode(PHPTAL::XHTML);
            $tpl->setSource('<'.$name.'>foo</'.$name.'>');
            $res = $tpl->execute();
            $this->assertEquals('<'.$name.'/>', $res);
        }
    }

   function testColgroup()
   {
        $code = '<colgroup>
<col class="col1" />
<col class="col2" />
<col class="col3" />
</colgroup>';

        $this->assertHTMLEquals($this->newPHPTAL()->setSource($code)->execute($code), $code);
   }

    function testBoolean()
    {
        $tpl = $this->newPHPTAL();
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
        $res = normalize_html($res);
        $exp = normalize_html('<html xmlns="http://www.w3.org/1999/xhtml">
                <body>
                    <input type="checkbox" checked="checked" />
                    <input type="text" readonly="readonly" />
                    <input type="radio" checked="checked" readonly="readonly" />
                    <input type="radio" />
                    <select>
                        <option selected="selected"></option>
                        <option></option><option selected="selected"></option><option></option><option selected="selected"></option><option></option><option selected="selected"></option>            </select>

                    <script defer="defer"></script>
                    <script defer="defer"></script>
                </body>
                </html>');
        $this->assertEquals($exp, $res);
    }

    function testBooleanOrNothing()
    {
        $tpl = $this->newPHPTAL()->setSource('
        <select>
          <option tal:repeat="option options" tal:attributes="value option/value;
        selected option/isSelected | nothing" tal:content="option/label"/>
        </select>');

        $tpl->options = array(
          array(
             'label' => 'Option1',
             'value' => 1
          ),
          array(
             'label'      => 'Option2',
             'value'      => 2,
             'isSelected' => true
          ),
          array(
             'label' => 'Option3',
             'value' => 3
          )
        );

        $this->assertEquals(normalize_html('<select>
          <option value="1">Option1</option>
          <option value="2" selected="selected">Option2</option>
          <option value="3">Option3</option>
        </select>'), normalize_html($tpl->execute()));
    }
}

