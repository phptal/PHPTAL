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

require_once 'I18NDummyTranslator.php';


class I18NAttributesTest extends PHPTAL_TestCase
{
    function testSingle()
    {
        $t = new DummyTranslator();
        $t->setTranslation('my-title', 'mon titre');

        $tpl = $this->newPHPTAL('input/i18n-attributes-01.html');
        $tpl->setTranslator($t);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/i18n-attributes-01.html');
        $this->assertEquals($exp, $res);
    }

    function testTranslateDefault()
    {
        $t = new DummyTranslator();
        $t->setTranslation('my-title', 'mon titre');

        $tpl = $this->newPHPTAL('input/i18n-attributes-02.html');
        $tpl->setTranslator($t);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/i18n-attributes-02.html');
        $this->assertEquals($exp, $res);
    }

    function testTranslateTalAttribute()
    {
        $t = new DummyTranslator();
        $t->setTranslation('my-title', 'mon titre');

        $tpl = $this->newPHPTAL('input/i18n-attributes-03.html');
        $tpl->sometitle = 'my-title';
        $tpl->setTranslator($t);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/i18n-attributes-03.html');
        $this->assertEquals($exp, $res, $tpl->getCodePath());
    }

    function testTranslateDefaultAttributeEscape()
    {
        $t = new DummyTranslator();
        $t->setTranslation('my\'title', 'mon\'titre');

        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><a title="my\'title" class="my&#039;title" i18n:attributes="class;title">test</a></div>');
        $tpl->sometitle = 'my-title';
        $tpl->setTranslator($t);
        $this->assertEquals('<div><a title="mon&#039;titre" class="mon&#039;titre">test</a></div>', $tpl->execute(), $tpl->getCodePath());
    }

    function testTranslateTalAttributeEscape()
    {
        $this->markTestSkipped("Hard to fix bug");

        $t = new DummyTranslator();
        $t->setTranslation('my\'title', 'mon\'titre');

        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><a title="foo" tal:attributes="title sometitle; class php:sometitle" i18n:attributes="class;title">test</a></div>');
        $tpl->sometitle = 'my\'title';
        $tpl->setTranslator($t);
        $this->assertEquals('<div><a title="mon&#039;titre" class="mon&#039;titre">test</a></div>', $tpl->execute(), $tpl->getCodePath());
    }

    function testMultiple()
    {
        $t = new DummyTranslator();
        $t->setTranslation('my-title', 'mon titre');
        $t->setTranslation('my-dummy', 'mon machin');

        $tpl = $this->newPHPTAL('input/i18n-attributes-04.html');
        $tpl->sometitle = 'my-title';
        $tpl->setTranslator($t);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/i18n-attributes-04.html');
        $this->assertEquals($exp, $res);
    }

    function testInterpolation()
    {
        $t = new DummyTranslator();
        $t->setTranslation('foo ${someObject/method} bar ${otherObject/method} buz', 'ok ${someObject/method} ok ${otherObject/method} ok');

        $tpl = $this->newPHPTAL('input/i18n-attributes-05.html');
        $tpl->setTranslator($t);
        $tpl->someObject = array('method' => 'good');
        $tpl->otherObject = array('method' => 'great');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/i18n-attributes-05.html');
        $this->assertEquals($exp, $res);
    }
}
