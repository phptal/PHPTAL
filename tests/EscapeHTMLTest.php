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


class EscapeHTMLTest extends PHPTAL_TestCase {

    private function executeString($str, $params = array())
    {
        $tpl = $this->newPHPTAL();
        foreach ($params as $k => $v) $tpl->set($k, $v);
        $tpl->setSource($str);
        return $tpl->execute();
    }

    function testDoesEscapeHTMLContent(){
        $tpl = $this->newPHPTAL('input/escape.html');
        $exp = normalize_html_file('output/escape.html');
        $res = normalize_html($tpl->execute());
        $this->assertEquals($exp, $res);
    }

    function testEntityDecodingPath1()
    {
        $res = $this->executeString('<div title="&quot;" class=\'&quot;\' tal:content="\'&quot; quote character\'" />');
        $this->assertNotContains('&amp;', $res);
    }

    function testEntityDecodingBeforePHP()
    {
        /* PHP block in attributes gets raw input (that's not XML style, but PHP style) */
        $res = $this->executeString('<div title="${php:strlen(\'&quot;&amp;\')}" class="<?php echo strlen(\'&quot;&amp;\')?>">'.
            '<tal:block tal:content="php:strlen(\'&quot;&amp;\')" />,${php:strlen(\'&quot;&amp;\')}</div>');
        $this->assertEquals('<div title="2" class="11">2,2</div>', $res);
    }

    function testEntityEncodingAfterPHP()
    {
        $res = $this->executeString('<div title="${php:urldecode(\'%26%22%3C\')}"><tal:block tal:content="php:urldecode(\'%26%22%3C\')" />,${php:urldecode(\'%26%22%3C\')}</div>');
        $this->assertEquals('<div title="&amp;&quot;&lt;">&amp;&quot;&lt;,&amp;&quot;&lt;</div>', $res);
    }

    function testNoEntityEncodingAfterStructurePHP()
    {
        $res = $this->executeString('<div title="${structure php:urldecode(\'%26%20%3E%27\')}" class="<?php echo urldecode(\'%26%20%3E%27\')?>">'.
            '<tal:block tal:content="structure php:urldecode(\'%26%20%3E%22\')" />,${structure php:urldecode(\'%26%20%3E%22\')},<?php echo urldecode(\'%26%20%3E%22\')?></div>');
        $this->assertEquals('<div title="& >\'" class="& >\'">& >",& >",& >"</div>', $res);
    }

    function testDecodingBeforeStructure()
    {
        $res = $this->executeString('<div tal:content="structure php:\'&amp; quote character\'" />');
        $this->assertNotContains('&amp;', $res);
    }

    function testEntityDecodingPHP1()
    {
        $res = $this->executeString('<div tal:content="php:\'&quot; quote character\'" />');
        $this->assertNotContains('&amp;', $res);
    }

    function testEntityDecodingPath2()
    {
        $res = $this->executeString('<div tal:attributes="title \'&quot; quote character\'" />');
        $this->assertNotContains('&amp;', $res);
    }

    function testEntityDecodingPHP2()
    {
        $res = $this->executeString('<div tal:attributes="title php:\'&quot; quote character\'" />');
        $this->assertNotContains('&amp;', $res);
    }

    function testEntityDecodingPath3()
    {
        $res = $this->executeString('<p>${\'&quot; quote character\'}</p>');
        $this->assertNotContains('&amp;', $res);
    }

    function testEntityDecodingPHP3()
    {
        $res = $this->executeString('<p>${php:\'&quot; quote character\'}</p>');
        $this->assertNotContains('&amp;', $res);
    }


    function testEntityEncodingPath1()
    {
        $res = $this->executeString('<div tal:content="\'&amp; ampersand character\'" />');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testEntityEncodingPHP1()
    {
        $res = $this->executeString('<div tal:content="php:\'&amp; ampersand character\'" />');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testEntityEncodingPath2()
    {
        $res = $this->executeString('<div tal:attributes="title \'&amp; ampersand character\'" />');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testEntityEncodingVariables()
    {
        $res = $this->executeString('<div tal:attributes="title variable; class variable">${variable}${php:variable}</div>',
                                    array('variable'=>'& = ampersand, " = quote, \' = apostrophe'));
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }

    function testEntityEncodingAttributesDefault1()
    {
        $res = $this->executeString('<div tal:attributes="title idontexist | default" title=\'&amp; ampersand character\' />');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testEntityEncodingAttributesDefault2()
    {
        $res = $this->executeString('<div tal:attributes="title idontexist | default" title=\'&quot;&apos;\' />');
        $this->assertNotContains('&amp;', $res);
        $this->assertContains('&quot;', $res); // or apos...
    }

    function testEntityEncodingPHP2()
    {
        $res = $this->executeString('<div tal:attributes="title php:\'&amp; ampersand character\'" />');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testEntityEncodingPath3()
    {
        $res = $this->executeString('<p>${\'&amp; ampersand character\'}</p>');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testEntityEncodingPHP3()
    {
        $res = $this->executeString('<p>&{php:\'&amp; ampersand character\'}</p>');
        $this->assertContains('&amp;', $res);
        $this->assertNotContains('&amp;amp;', $res);
        $this->assertNotContains('&amp;&amp;', $res);
    }

    function testSimpleXML()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>${x} ${y}</p>');
        $simplexml = new SimpleXMLElement('<foo title="bar&amp;&lt;" empty="">foo&amp;&lt;</foo>');

        $tpl->x = $simplexml['title'];
        $tpl->y = $simplexml['empty'];
        $this->assertEquals('<p>bar&amp;&lt; </p>', $tpl->execute());
    }

    function testStructureSimpleXML()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>${structure x} ${structure y}</p>');
        $simplexml = new SimpleXMLElement('<foo title="bar&amp;&lt;" empty="">foo&amp;&lt;</foo>');

        $tpl->x = $simplexml['title'];
        $tpl->y = $simplexml['empty'];
        $this->assertEquals('<p>bar&< </p>', $tpl->execute());
    }

    function testUnicodeUnescaped()
    {
        $tpl = $this->newPHPTAL();
        $tpl->World = '${World}'; // a quine! ;)
        $tpl->setSource($src = '<p>Hello “${World}!”</p>');

        $this->assertEquals($src, $tpl->execute());
    }
}
