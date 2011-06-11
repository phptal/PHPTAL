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


class ContentEncodingTest extends PHPTAL_TestCase
{
    function testSimpleAnyForm()
    {
        $tpl = $this->newPHPTAL('input/content-encoding.xml');
        $res = $tpl->execute();
        $exp = html_entity_decode(normalize_html_file('output/content-encoding.xml'), ENT_QUOTES, 'UTF-8');
        $res = html_entity_decode(normalize_html($res), ENT_QUOTES, 'UTF-8');
        $this->assertEquals($exp, $res);
    }

    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/content-encoding.xml');
        $res = $tpl->execute();
        $exp = normalize_html_file('output/content-encoding.xml');
        $res = normalize_html($res);
        $this->assertEquals($exp, $res);
    }

    function testEchoArray()
    {
        $p = $this->newPHPTAL();
        $p->setSource('<p tal:content="foo"/>');
        $p->foo = array('bar'=>'a&aa', '<bbb>', null, -1, false);
        $this->assertEquals('<p>a&amp;aa, &lt;bbb&gt;, , -1, 0</p>', $p->execute());
    }
}

