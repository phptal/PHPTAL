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




class DummyDefinePhpNode extends PHPTAL_Dom_Element {
    function __construct() {}
    function generateCode(PHPTAL_Php_CodeWriter $codewriter) {}
}

class TalDefineTest extends PHPTAL_TestCase
{
    function testExpressionParser()
    {
        $att = new PHPTAL_Php_Attribute_TAL_Define(new DummyDefinePhpNode(), 'a b');

        list($defineScope, $defineVar, $expression) = $att->parseExpression('local a_234z b');
        $this->assertEquals('local', $defineScope);
        $this->assertEquals('a_234z', $defineVar);
        $this->assertEquals('b', $expression);

        list($defineScope, $defineVar, $expression) = $att->parseExpression('global a_234z b');
        $this->assertEquals('global', $defineScope);
        $this->assertEquals('a_234z', $defineVar);
        $this->assertEquals('b', $expression);

        list($defineScope, $defineVar, $expression) = $att->parseExpression('a_234Z b');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('a_234Z', $defineVar);
        $this->assertEquals('b', $expression);

        list($defineScope, $defineVar, $expression) = $att->parseExpression('a');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('a', $defineVar);
        $this->assertEquals(null, $expression);

        list($defineScope, $defineVar, $expression) = $att->parseExpression('global a string: foo; bar; baz');
        $this->assertEquals('global', $defineScope);
        $this->assertEquals('a', $defineVar);
        $this->assertEquals('string: foo; bar; baz', $expression);


        list($defineScope, $defineVar, $expression) = $att->parseExpression('foo this != other');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('foo', $defineVar);
        $this->assertEquals('this != other', $expression);

        list($defineScope, $defineVar, $expression) = $att->parseExpression('x exists: a | not: b | path: c | 128');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('x', $defineVar);
        $this->assertEquals('exists: a | not: b | path: c | 128', $expression);
    }

    function testMulti()
    {
        $tpl = $this->newPHPTAL('input/tal-define.01.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.01.html');
        $this->assertEquals($exp, $res);
    }

    function testBuffered()
    {
        $tpl = $this->newPHPTAL('input/tal-define.02.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.02.html');
        $this->assertEquals($exp, $res);
    }

    function testMultiChained()
    {
        $tpl = $this->newPHPTAL('input/tal-define.03.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.03.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineZero()
    {
        $tpl = $this->newPHPTAL('input/tal-define.04.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.04.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineZeroTalesPHP()
    {
        $tpl = $this->newPHPTAL('input/tal-define.05.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.05.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineInMacro()
    {
        $tpl = $this->newPHPTAL('input/tal-define.06.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.06.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineDoNotStealOutput()
    {
        $tpl = $this->newPHPTAL('input/tal-define.07.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.07.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineWithRepeatAndContent()
    {
        $tpl = $this->newPHPTAL('input/tal-define.08.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.08.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineWithUseMacro()
    {
        $tpl = $this->newPHPTAL('input/tal-define.09.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.09.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineAndPrint()
    {
        $tpl = $this->newPHPTAL('input/tal-define.10.html');
        $tpl->fname = 'Roger';
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.10.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineContent()
    {
        $tpl = $this->newPHPTAL('input/tal-define.11.html');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.11.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineAndAttributes()
    {
        $tpl = $this->newPHPTAL('input/tal-define.12.html');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-define.12.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineGlobal()
    {
        $exp = normalize_html_file('output/tal-define.13.html');
        $tpl = $this->newPHPTAL('input/tal-define.13.html');
        $res = normalize_html($tpl->execute());
        $this->assertEquals($exp, $res);
    }

    function testDefineAlter()
    {
        $exp = normalize_html_file('output/tal-define.14.html');
        $tpl = $this->newPHPTAL('input/tal-define.14.html');
        $res = normalize_html($tpl->execute());
        $this->assertEquals($exp, $res);
    }

    function testDefineSemicolon()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:define="one \';;\'; two string:;;;;; three php:\';;;;;;\'">${one}-${two}-${three}</p>');
        $this->assertEquals('<p>;-;;-;;;</p>', $tpl->execute());
    }

    function testEmpty()
    {
        $tal = $this->newPHPTAL();
        $tal->setSource('<div class="blank_bg" tal:define="book relative/book" tal:condition="php: count(book)>0"></div>');
        $tal->relative = array('book'=>1);

        $this->assertEquals($tal->execute(), '<div class="blank_bg"></div>');
    }

    function testGlobalDefineEmptySpan()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div>
           <span tal:define="global x \'ok\'" />
           ${x}
        </div>
        ');
        $res = normalize_html($tpl->execute());
        $this->assertEquals(normalize_html('<div> ok </div>'), $res);
    }

    function testGlobalDefineEmptySpan2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div>
           <span tal:define="global x \'ok\'" tal:comment="ignoreme" />
           ${x}
        </div>
        ');
        $res = normalize_html($tpl->execute());
        $this->assertEquals(normalize_html('<div> ok </div>'), $res);
    }


    function testGlobalDefineNonEmptySpan()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::XML);

        $tpl->setSource('<div>
           <span tal:define="global x \'ok\'" class="foo" />
           ${x}
        </div>
        ');
        $res = normalize_html($tpl->execute());
        $this->assertEquals(normalize_html('<div> <span class="foo"/> ok </div>'), $res);
    }

    function testGlobalDefineNonEmptySpan2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setOutputMode(PHPTAL::XML);

        $tpl->setSource('<div>
           <span tal:define="global x \'ok\'" tal:attributes="class \'foo\'" />
           ${x}
        </div>
        ');
        $res = normalize_html($tpl->execute());
        $this->assertEquals(normalize_html('<div> <span class="foo"/> ok </div>'), $res);
    }

    function testDefineTALESInterpolated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->varvar = 'ok';
        $tpl->varname = 'varvar';
        $tpl->setSource('<div tal:define="test ${varname}">${test}</div>');
        $this->assertEquals('<div>ok</div>', $tpl->execute());
    }

    function testDefinePHPInterpolated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->varvar = 'ok';
        $tpl->varname = 'varvar';
        $tpl->setSource('<div tal:define="test php:${varname}">${test}</div>');
        $this->assertEquals('<div>ok</div>', $tpl->execute());
    }

    const VARNAME = 'varvar';

    function testDefinePHPConstInterpolated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->varvar = 'ok';
        $tpl->varname = 'varvar';
        $tpl->setSource('<div tal:define="test php:${'.get_class($this).'::VARNAME}">${test}</div>');
        $this->assertEquals('<div>ok</div>', $tpl->execute());
    }

    function testRedefineSelf()
    {
        $tpl = $this->newPHPTAL();
        $tpl->label = 'label var';
        $tpl->fail = 'not an array';
        $tpl->setSource('<tal:block tal:define="label fail/label|label" tal:replace="structure label"/>');

        $this->assertEquals('label var', $tpl->execute());
    }

    function testRedefineSelf2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->label = 'label var';
        $tpl->fail = 'not an array';
        $tpl->setSource('<tal:block tal:define="label fail/label|label|somethingelse" tal:replace="structure label"/>');

        $this->assertEquals('label var', $tpl->execute());
    }

    /**
     * @expectedException PHPTAL_TemplateException
     */
    function testRejectsInvalidExpression()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<x tal:define="global foo | default"/>');
        $tpl->execute();
    }

    function testHasRealContent()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<y
        phptal:debug="">

        <x
        tal:define="global foo bar | default"
        >
        test
        </x>
        </y>
        ');
        $tpl->execute();
    }

    function testHasRealCDATAContent()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<script tal:define="global foo bar | default"><![CDATA[ x ]]></script>');
        $tpl->execute();
    }


    function testDefineAndAttributesOnSameElement()
    {
        $tpl = $this->newPHPTAL();
        $tpl->team = 'zzz';
        $tpl->row = 'zzz';
        $tpl->event_name = 'zzz';
        $tpl->setSource('<tal:block tal:condition="php: isset(row.$team.$event_name)">
                        <td tal:define="event php: row.$team.$event_name" tal:attributes="style \'THIS DOESNT WORK\'">
                           ${event/player/surname}
                       </td>
                   </tal:block>');
        $tpl->execute();
    }
}
