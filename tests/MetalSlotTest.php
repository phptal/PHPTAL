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
    
    function testPreservesContext()
    {
        $tpl = $this->newPHPTAL('input/metal-slot.05.html');
        $tpl->var = "top";
        $this->assertEquals(trim_string('top=top<div>inusemacro=inusemacro<div>inmacro=inmacro<div>infillslot=infillslot</div>/inmacro=inmacro</div>/inusemacro=inusemacro</div>/top=top'),
                            trim_string($tpl->execute()), $tpl->getCodePath());
    }

    function testPreservesTopmostContext()
    {
        $tpl = $this->newPHPTAL();
        $tpl->var = "topmost";
        $tpl->setSource('
            <div metal:define-macro="m">
                <div tal:define="var string:invalid">
                    <span metal:define-slot="s">empty slot</span>
                </div>    
            </div>
            
            <div metal:use-macro="m">
                <div metal:fill-slot="s">var = ${var}</div>
            </div>    
        ');
        $this->assertEquals(trim_string('<div><div><div>var = topmost</div></div></div>'),trim_string($tpl->execute()), $tpl->getCodePath());
    }
    
    function testRecursiveFillSimple()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('
            <div>
              <div metal:define-macro="test1">
                test1 macro value:<span metal:define-slot="value">a value should go here</span>
              </div>

              <div metal:define-macro="test2">
                test2 macro value:<span metal:define-slot="value">a value should go here</span>                
              </div>

              <div metal:use-macro="test1" class="calls test1 macro">
                <div metal:fill-slot="value" class="filling value for test1">
                  <div metal:use-macro="test2" class="calls test2 macro">
                    <span metal:fill-slot="value" class="filling value for test2">foo bar baz</span>
                  </div>
                </div>
              </div>
            </div>
            ');
            
        $this->assertEquals(trim_string('<div><div>test1 macro value:<div class="filling value for test1">
        <div>test2 macro value:<span class="filling value for test2">foo bar baz</span></div></div></div></div>'),
                            trim_string($tpl->execute()), $tpl->getCodePath());
    }

    function testRecusiveFill()
    {
        $tpl = $this->newPHPTAL('input/metal-slot.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.02.html');
        $this->assertEquals($exp, $res, $tpl->getCodePath());
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
    
    /**
     * this is violation of TAL specification, but needs to work for backwards compatibility
     */
    function testFillPreservedAcrossCalls()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<tal:block metal:fill-slot="foo">foocontent</tal:block>');
        $tpl->execute();
        $tpl->setSource('<tal:block metal:define-slot="foo">FAIL</tal:block>');
        
        $this->assertEquals('foocontent', $tpl->execute());
    }
    
    /**
     * this is violation of TAL specification, but needs to work for backwards compatibility
     */
    function testFillPreservedAcrossCalls2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:define="x string:x"><tal:block metal:fill-slot="foo">foocontent</tal:block></p>');
        $tpl->execute();
        $tpl->setSource('<p tal:define="y string:y"><tal:block metal:define-slot="foo">FAIL</tal:block></p>');
        
        $this->assertEquals('<p>foocontent</p>', $tpl->execute());
    }
    
    function testUsesCallbackForLargeSlots()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('
        <x metal:define-macro="foo"><y metal:define-slot="s"/></x>
        
        <f metal:use-macro="foo"><s metal:fill-slot="s">
            <loop tal:repeat="n php:range(1,5)">
                <inner tal:repeat="y php:range(1,5)">
                    <a title="stuff lots of stuff stuff lots of stuff stuff lots of stuff stuff lots of stuff lots of stuff stuff lots of stuff">stuff</a>
                    <a title="stuff lots of stuff stuff lots of stuff stuff lots of stuff stuff lots of stuff lots of stuff stuff lots of stuff">stuff</a>
                    <a title="stuff lots of stuff stuff lots of stuff stuff lots of stuff stuff lots of stuff lots of stuff stuff lots of stuff">stuff</a>
                </inner>    
            </loop>    
        </s></f>
        ');
        
        $res = $tpl->execute();        
        $this->assertGreaterThan(PHPTAL_Php_Attribute_METAL_FillSlot::CALLBACK_THRESHOLD,strlen($res));
        
        $tpl_php_source = file_get_contents($tpl->getCodePath());
        
        $this->assertNotContains("fillSlot(",$tpl_php_source);        
        $this->assertContains("fillSlotCallback(",$tpl_php_source);
    }

    function testUsesBufferForSmallSlots()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('
        <x metal:define-macro="foo"><y metal:define-slot="s"/></x>
        
        <f metal:use-macro="foo"><s metal:fill-slot="s">
            stuff lots of stuff stuff lots of stuff stuff lots of stuff stuff lots of stuff stuff lots of stuff stuff lots of stuff
        </s></f>
        ');
        
        $tpl->execute();
        
        $tpl_php_source = file_get_contents($tpl->getCodePath());
        
        $this->assertNotContains("fillSlotCallback(",$tpl_php_source);
        $this->assertContains("fillSlot(",$tpl_php_source);        
    }

}


