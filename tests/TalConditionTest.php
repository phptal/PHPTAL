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

require_once dirname(__FILE__)."/config.php";

PHPTAL::setIncludePath();
require_once 'PHPTAL/Dom/DocumentBuilder.php';
PHPTAL::restoreIncludePath();

class TalConditionTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/tal-condition.01.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-condition.01.html');
        $this->assertEquals($exp, $res);
    }

    function testNot()
    {
        $tpl = $this->newPHPTAL('input/tal-condition.02.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-condition.02.html');
        $this->assertEquals($exp, $res);
    }

    function testExists()
    {
        $tpl = $this->newPHPTAL('input/tal-condition.03.html');
        $tpl->somevar = true;
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-condition.03.html');
        $this->assertEquals($exp, $res);
    }

    function testException()
    {
        $tpl = $this->newPHPTAL('input/tal-condition.04.html');
        $tpl->somevar = true;
        try {
            $tpl->execute();
        }
        catch (Exception $e){
        }
        $this->assertEquals(true, isset($e));
        // $exp = normalize_html_file('output/tal-condition.04.html');
        // $this->assertEquals($exp, $res);
    }

    function testChainedFalse()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<tal:block tal:condition="foo | bar | baz | nothing">fail!</tal:block>');
        $res = $tpl->execute();
        $this->assertEquals($res,'');
    }

    function testChainedTrue()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<tal:block tal:condition="foo | bar | baz | \'ok!\'">ok</tal:block>');
        $res = $tpl->execute();
        $this->assertEquals($res,'ok');
    }

    function testChainedShortCircuit()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<tal:block tal:condition="foo | \'ok!\' | bar | nothing">ok</tal:block>');
        $res = $tpl->execute();
        $this->assertEquals($res,'ok');
    }
}
