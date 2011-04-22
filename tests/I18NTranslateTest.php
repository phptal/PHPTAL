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


class I18NTranslateTest extends PHPTAL_TestCase
{
    /**
     * @expectedException PHPTAL_ConfigurationException
     */
    function testFailsWhenTranslatorNotSet()
    {
        $tpl = $this->newPHPTAL('input/i18n-translate-01.html');
        $tpl->execute();
    }

    function testStringTranslate()
    {
        $tpl = $this->newPHPTAL('input/i18n-translate-01.html');
        $tpl->setTranslator( new DummyTranslator() );
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/i18n-translate-01.html');
        $this->assertEquals($exp, $res);
    }

    function testEvalTranslate()
    {
        $tpl = $this->newPHPTAL('input/i18n-translate-02.html');
        $tpl->setTranslator( new DummyTranslator() );
        $tpl->message = "my translate key &";
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/i18n-translate-02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructureTranslate()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( new DummyTranslator() );
        $tpl->setSource('<p i18n:translate="structure \'translate<b>this</b>\'"/>');
        $this->assertEquals('<p>translate<b>this</b></p>', $tpl->execute());
    }

    function testStructureTranslate2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( new DummyTranslator() );
        $tpl->setSource('<p i18n:translate="structure">
        translate
        <b class="foo&amp;bar">
        this
        </b>
        </p>');
        $this->assertEquals('<p>translate <b class="foo&amp;bar"> this </b></p>', $tpl->execute());
    }

    function testStructureTranslate3()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );
        $t->setTranslation('msg', '<b class="foo&amp;bar">translated&nbsp;key</b>');
        $tpl->var = 'msg';
        $tpl->setSource('<div>
        <p i18n:translate="var"/>
        <p i18n:translate="structure var"/>
        </div>');
        $this->assertEquals(normalize_html('<div>
        <p>&lt;b class=&quot;foo&amp;amp;bar&quot;&gt;translated&amp;nbsp;key&lt;/b&gt;</p>
        <p><b class="foo&amp;bar">translated&nbsp;key</b></p>
        </div>'), normalize_html($tpl->execute()));
    }


    function testDomain()
    {
        $tpl = $this->newPHPTAL();

        $tpl->bar = 'baz';

        $tpl->setTranslator( $t = new DummyTranslator() );
        $tpl->t = $t;

        $tpl->setSource('<div i18n:domain="foo${bar}$${quz}">${t/domain}</div>');
        $this->assertEquals(normalize_html('<div>foobaz${quz}</div>'), normalize_html($tpl->execute()));

    }

    function testPHPTalesDomain()
    {
        $tpl = $this->newPHPTAL();

        $tpl->bar = '1';

        $tpl->setTranslator( $t = new DummyTranslator() );
        $tpl->t = $t;

        $tpl->setSource('<div phptal:tales="php" i18n:domain="foo${bar+1}$${quz}">${t.domain}</div>');
        $this->assertEquals(normalize_html('<div>foo2${quz}</div>'), normalize_html($tpl->execute()));
    }

    function testTranslateChain()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );
        $t->setTranslation('bar', '<bar> translated');

        $tpl->setSource('<div i18n:translate="foo | string:bar">not translated</div>');

        $this->assertEquals('<div>&lt;bar&gt; translated</div>', $tpl->execute());
    }

    function testTranslateChainString()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );

        $tpl->setSource('<div i18n:translate="foo | string:&lt;bar> translated">not translated</div>');

        $this->assertEquals('<div>&lt;bar&gt; translated</div>', $tpl->execute());
    }

    function testTranslateChainExists()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );
        $tpl->foo = '<foo> value';

        $tpl->setSource('<div i18n:translate="foo | string:&lt;bar> translated">not translated</div>');

        $this->assertEquals('<div>&lt;foo&gt; value</div>', $tpl->execute());
    }

    function testTranslateChainExistsTranslated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );
        $t->setTranslation('<foo> value', '<foo> translated');

        $tpl->foo = '<foo> value';

        $tpl->setSource('<div i18n:translate="foo | string:&lt;bar> translated">not translated</div>');

        $this->assertEquals('<div>&lt;foo&gt; translated</div>', $tpl->execute());
    }

    /**
     * @expectedException PHPTAL_TemplateException
     */
    function testRejectsEmptyKey()
    {
        $this->newPHPTAL()->setTranslator( $t = new DummyTranslator() )->setSource('<div i18n:translate=""></div>')->execute();
    }

    /**
     * @expectedException PHPTAL_TemplateException
     */
    function testRejectsEmptyKeyMarkup()
    {
        $this->newPHPTAL()->setTranslator( $t = new DummyTranslator() )->setSource('<div i18n:translate=""> <span tal:content="string:test"> </span> </div>')->execute();
    }


    function testTranslateChainStructureExistsTranslated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );
        $t->setTranslation('<foo> value', '<foo> translated');

        $tpl->foo = '<foo> value';

        $tpl->setSource('<div i18n:translate="structure foo | string:&lt;bar> translated">not translated</div>');

        $this->assertEquals('<div><foo> translated</div>', $tpl->execute());
    }
}
