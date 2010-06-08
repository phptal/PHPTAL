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


class EscapeCDATATest extends PHPTAL_TestCase {

    private $last_code_path;

    private function executeString($str, $params = array())
    {
        $tpl = $this->newPHPTAL();
        foreach ($params as $k => $v) $tpl->set($k, $v);
        $tpl->setSource($str);
        $this->last_code_path = $tpl->getCodePath();
        return $tpl->execute();
    }

    function testTrimString()
    {
        $this->assertEquals(
            normalize_html('<foo><bar>]]&gt; foo ]> bar</bar></foo>'),
            normalize_html('<foo> <bar>]]&gt; foo ]&gt; bar </bar> </foo>')
        );

        $this->assertNotEquals(
            normalize_html('foo]]>bar'),
            normalize_html('foo]]&gt;bar')
        );

    }

    function testDoesEscapeHTMLContent(){
        $tpl = $this->newPHPTAL('input/escape.html');
        $exp = normalize_html_file('output/escape.html');
        $res = normalize_html($tpl->execute());
        $this->assertEquals($exp, $res);
    }

    function testEntityTextInPath()
    {
        $res = $this->executeString('<div><![CDATA[${text \'"< & &amp; &quot; &lt;\'},${false | string:"< & &amp; &quot; &lt;}]]></div>');

        // either way is good
        if (false !== strpos($res, '<![CDATA[')) {
            $this->assertEquals('<div><![CDATA["< & &amp; &quot; &lt;,"< & &amp; &quot; &lt;]]></div>', $res);
        } else {
            $this->assertEquals('<div>&quot;&lt; &amp; &amp;amp; &amp;quot; &amp;lt;,&quot;&lt; &amp; &amp;amp; &amp;quot; &amp;lt;</div>', $res);
        }
    }

    function testEntityStructureInPath()
    {
        $res = $this->executeString('<div><![CDATA[${structure \'"< & &amp; &quot; &lt;\'},${structure false | string:"< & &amp; &quot; &lt;}]]></div>');
        $this->assertEquals('<div><![CDATA["< & &amp; &quot; &lt;,"< & &amp; &quot; &lt;]]></div>', $res);
    }

    function testEntityInContentPHP()
    {
        $res = $this->executeString('<div><![CDATA[${php:strlen(\'&quot;&amp;&lt;\')},${php:strlen(\'<"&\')}]]></div>');
        $this->assertEquals('<div>15,3</div>', $res);
    }

    function testEntityInScriptPHP()
    {
        $res = $this->executeString('<script><![CDATA[${php:strlen(\'&quot;&amp;&lt;\')},${php:strlen(\'<"&\')}]]></script>');
        $this->assertEquals('<script><![CDATA[15,3]]></script>', $res);
    }

    function testEntityInPHP2()
    {
        $res = $this->executeString('<div><![CDATA[${structure php:strlen(\'&quot;&amp;&lt;\')},${structure php:strlen(\'<"&\')}]]></div>');
        $this->assertEquals('<div><![CDATA[15,3]]></div>', $res);
    }

    function testEntityInPHP3()
    {
        $res = $this->executeString('<div><![CDATA[<?php echo strlen(\'&quot;&amp;&lt;\')?>,<?php echo strlen(\'<"&\') ?>]]></div>');
        $this->assertEquals('<div><![CDATA[15,3]]></div>', $res);
    }

    function testNoEncodingAfterPHP()
    {
        $res = $this->executeString('<div><![CDATA[${php:urldecode(\'%26%22%3C\')},${structure php:urldecode(\'%26%22%3C\')},<?php echo urldecode(\'%26%22%3C\') ?>]]></div>');
        $this->assertEquals('<div><![CDATA[&"<,&"<,&"<]]></div>', $res);
    }

    /**
     * normal XML behavior expected
     */
    function testEscapeCDATAXML()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::XML);
        $tpl->setSource('<y><![CDATA[${cdata}; ${php:cdata};]]></y>     <y><![CDATA[${structure cdata}]]></y>');
        $tpl->cdata = ']]></x>';
        $res = $tpl->execute();
        $this->assertEquals('<y>]]&gt;&lt;/x&gt;; ]]&gt;&lt;/x&gt;;</y>     <y><![CDATA[]]></x>]]></y>', $res);
    }

    /**
     * ugly hybrid between HTML (XHTML as text/html) and XML
     */
    function testEscapeCDATAXHTML()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::XHTML);
        $tpl->setSource('<script><![CDATA[${cdata}; ${php:cdata};]]></script>     <y><![CDATA[${structure cdata}]]></y>');
        $tpl->cdata = ']]></x>';
        $res = $tpl->execute();
        $this->assertEquals('<script><![CDATA[]]]]><![CDATA[><\/x>; ]]]]><![CDATA[><\/x>;]]></script>     <y><![CDATA[]]></x>]]></y>', $res);
    }


    function testEscapeCDATAHTML()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setSource('<y><![CDATA[${cdata}; ${php:cdata};]]></y>     <y><![CDATA[${structure cdata}]]></y>');
        $tpl->cdata = ']]></x>';
        $res = $tpl->execute();
        $this->assertEquals('<y>]]&gt;&lt;/x&gt;; ]]&gt;&lt;/x&gt;;</y>     <y>]]></x></y>', $res);
    }



    function testAutoCDATA()
    {
        $res = $this->executeString('<script> 1 < 2 </script>');
        $this->assertEquals('<script>/*<![CDATA[*/ 1 < 2 /*]]>*/</script>', $res, $this->last_code_path);
    }

    function testAutoCDATA2()
    {
        $res = $this->executeString('<xhtmlz:script xmlns:xhtmlz="http://www.w3.org/1999/xhtml"> 1 < 2 ${php:\'&\' . \'&amp;\'} </xhtmlz:script>');
        $this->assertEquals('<xhtmlz:script xmlns:xhtmlz="http://www.w3.org/1999/xhtml">/*<![CDATA[*/ 1 < 2 && /*]]>*/</xhtmlz:script>', $res, $this->last_code_path);
    }

    function testNoAutoCDATA()
    {
        $res = $this->executeString('<script> "1 \' 2" </script><script xmlns="foo"> 1 &lt; 2 </script>');
        $this->assertEquals('<script> "1 \' 2" </script><script xmlns="foo"> 1 &lt; 2 </script>', $res, $this->last_code_path);
    }

    function testNoAutoCDATA2()
    {
        $res = $this->executeString('<script> a && ${structure foo} </script><script xmlns="foo"> 1 &lt; 2 </script>', array('foo'=>'<foo/>'));
        $this->assertEquals('<script> a &amp;&amp; <foo/> </script><script xmlns="foo"> 1 &lt; 2 </script>', $res, $this->last_code_path);
    }

    function testNoAutoCDATA3()
    {
        $res = $this->executeString('<style> html > body </style>');
        $this->assertEquals('<style> html > body </style>', $res, $this->last_code_path);
    }
}
