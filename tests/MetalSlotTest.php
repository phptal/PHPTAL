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

class MetalSlotTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/metal-slot.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.01.html');
        $this->assertEquals($exp, $res);
    }

    function testRecusiveFill()
    {
        $tpl = $this->newPHPTAL('input/metal-slot.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.02.html');
        $this->assertEquals($exp, $res);
    }

    function testBlock()
    {
        $tpl = $this->newPHPTAL('input/metal-slot.03.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.03.html');
        $this->assertEquals($exp, $res);
    }

    function testFillAndCondition()
    {
        $tpl = $this->newPHPTAL('input/metal-slot.04.html');
        $tpl->fillit = true;
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.04.html');
        $this->assertEquals($exp, $res);
    }

    function testFillWithi18n()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('
        <div metal:use-macro="m1"><p metal:fill-slot="test-slot"><span i18n:translate="">translatemetoo</span></p></div>
        <div metal:define-macro="m1">
            <p metal:define-slot="test-slot" i18n:attributes="title" title="translateme">test</p>
        </div>
        ');

        $tr = new DummyTranslator();
        $tr->setTranslation("translateme","translatedyou");
        $tr->setTranslation("translatemetoo","translatedyouaswell");
        $tpl->setTranslator($tr);

        $this->assertEquals(trim_string('<div><p><span>translatedyouaswell</span></p></div>'),trim_string($tpl->execute()), $tpl->getCodePath());
    }
}


