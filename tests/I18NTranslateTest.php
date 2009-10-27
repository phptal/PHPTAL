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
require_once 'I18NDummyTranslator.php';


class I18NTranslateTest extends PHPTAL_TestCase
{
    function testStringTranslate()
    {
        $tpl = $this->newPHPTAL('input/i18n-translate-01.html');
        $tpl->setTranslator( new DummyTranslator() );
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/i18n-translate-01.html');
        $this->assertEquals($exp, $res);
    }

    function testEvalTranslate()
    {
        $tpl = $this->newPHPTAL('input/i18n-translate-02.html');
        $tpl->setTranslator( new DummyTranslator() );
        $tpl->message = "my translate key &";
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/i18n-translate-02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructureTranslate()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( new DummyTranslator() );
        $tpl->setSource('<p i18n:translate="structure \'translate<b>this</b>\'"/>');
        $this->assertEquals('<p>translate<b>this</b></p>',$tpl->execute());
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
        $this->assertEquals('<p>translate <b class="foo&amp;bar"> this </b></p>',$tpl->execute());
    }

    function testStructureTranslate3()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTranslator( $t = new DummyTranslator() );
        $t->setTranslation('msg','<b class="foo&amp;bar">translated&nbsp;key</b>');
        $tpl->var = 'msg';
        $tpl->setSource('<div>
        <p i18n:translate="var"/>
        <p i18n:translate="structure var"/>
        </div>');
        $this->assertEquals(trim_string('<div>
        <p>&lt;b class=&quot;foo&amp;amp;bar&quot;&gt;translated&amp;nbsp;key&lt;/b&gt;</p>
        <p><b class="foo&amp;bar">translated&nbsp;key</b></p>
        </div>'),trim_string($tpl->execute()));
    }
    
    
    function testDomain()
    {
        $tpl = $this->newPHPTAL();
        
        $tpl->bar = 'baz';
        
        $tpl->setTranslator( $t = new DummyTranslator() );
        $tpl->t = $t;
                
        $tpl->setSource('<div i18n:domain="foo${bar}$${quz}">${t/domain}</div>');
        $this->assertEquals(trim_string('<div>foobaz${quz}</div>'),trim_string($tpl->execute()));
        
    }

    function testPHPTalesDomain()
    {
        $tpl = $this->newPHPTAL();
        
        $tpl->bar = '1';
        
        $tpl->setTranslator( $t = new DummyTranslator() );
        $tpl->t = $t;
                
        $tpl->setSource('<div phptal:tales="php" i18n:domain="foo${bar+1}$${quz}">${t.domain}</div>');
        $this->assertEquals(trim_string('<div>foo2${quz}</div>'),trim_string($tpl->execute()));
    
    }
}
