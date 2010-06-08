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



class TalOmitTagTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/tal-omit-tag.01.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-omit-tag.01.html');
        $this->assertEquals($exp, $res);
    }

    function testWithCondition()
    {
        $tpl = $this->newPHPTAL('input/tal-omit-tag.02.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-omit-tag.02.html');
        $this->assertEquals($exp, $res);
    }

    private $call_count;
    function callCount()
    {
        $this->call_count++;
    }

    function testCalledOnlyOnce()
    {
        $this->call_count=0;
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:omit-tag="test/callCount" />');

        $tpl->test = $this;
        $tpl->execute();
        $this->assertEquals(1, $this->call_count);

        $tpl->execute();
        $this->assertEquals(2, $this->call_count);
    }

    function testNestedConditions()
    {
        $this->call_count=0;
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<span tal:omit-tag="php:true">a<span tal:omit-tag="php:false">b<span tal:omit-tag="php:true">c<span tal:omit-tag="php:false">d<span tal:omit-tag="php:false">e<span tal:omit-tag="php:true">f<span tal:omit-tag="php:true">g</span>h</span>i</span>j</span>k</span></span></span>');

        $this->assertEquals('a<span>bc<span>d<span>efghi</span>j</span>k</span>', $tpl->execute());
    }
}

