<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  


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
        $this->assertEquals('<div>
        <p>&lt;b class=&quot;foo&amp;amp;bar&quot;&gt;translated&amp;nbsp;key&lt;/b&gt;</p>
        <p><b class="foo&amp;bar">translated&nbsp;key</b></p>
        </div>',$tpl->execute());
    }
}
