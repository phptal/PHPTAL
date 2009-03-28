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

require_once 'config.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Node.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Define.php';


if (!class_exists('DummyPhpNode')) {
    class DummyPhpNode extends PHPTAL_DOMElement {
        function __construct() {}
        function generate(PHPTAL_Php_CodeWriter $codewriter) {}
    }
}

class TalDefineTest extends PHPTAL_TestCase 
{
    function testExpressionParser()
    {
        $att = new PHPTAL_Php_Attribute_Tal_Define(new DummyPhpNode(),'a b');
        
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
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.01.html');
        $this->assertEquals($exp, $res);
    }

    function testBuffered()
    {
        $tpl = $this->newPHPTAL('input/tal-define.02.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testMultiChained()
    {
        $tpl = $this->newPHPTAL('input/tal-define.03.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.03.html');
        $this->assertEquals($exp, $res);        
    }

    function testDefineZero()
    {
        $tpl = $this->newPHPTAL('input/tal-define.04.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.04.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineZeroTalesPHP()
    {
        $tpl = $this->newPHPTAL('input/tal-define.05.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.05.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineInMacro()
    {
        $tpl = $this->newPHPTAL('input/tal-define.06.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.06.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineDoNotStealOutput()
    {
        $tpl = $this->newPHPTAL('input/tal-define.07.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.07.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineWithRepeatAndContent()
    {
        $tpl = $this->newPHPTAL('input/tal-define.08.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.08.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineWithUseMacro()
    {
        $tpl = $this->newPHPTAL('input/tal-define.09.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.09.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineAndPrint()
    {
        $tpl = $this->newPHPTAL('input/tal-define.10.html');
        $tpl->fname = 'Roger';
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.10.html');
        $this->assertEquals($exp, $res);        
    }

    function testDefineContent()
    {
        $tpl = $this->newPHPTAL('input/tal-define.11.html');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.11.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineAndAttributes()
    {
        $tpl = $this->newPHPTAL('input/tal-define.12.html');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.12.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineGlobal()
    {
        $exp = trim_file('output/tal-define.13.html');
        $tpl = $this->newPHPTAL('input/tal-define.13.html');
        $res = trim_string($tpl->execute());
        $this->assertEquals($exp, $res);
    }

    function testDefineAlter()
    {
        $exp = trim_file('output/tal-define.14.html');
        $tpl = $this->newPHPTAL('input/tal-define.14.html');
        $res = trim_string($tpl->execute());
        $this->assertEquals($exp, $res);
    }
    
    function testDefineSemicolon()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p tal:define="one \';;\'; two string:;;;;; three php:\';;;;;;\'">${one}-${two}-${three}</p>');
        $this->assertEquals('<p>;-;;-;;;</p>',$tpl->execute());
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
        $res = trim_string($tpl->execute());
        $this->assertEquals(trim_string('<div> ok </div>'), $res);
    }
    
    function testGlobalDefineEmptySpan2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div>
           <span tal:define="global x \'ok\'" tal:comment="ignoreme" />
           ${x}
        </div>
        ');
        $res = trim_string($tpl->execute());
        $this->assertEquals(trim_string('<div> ok </div>'), $res);
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
        $res = trim_string($tpl->execute());
        $this->assertEquals(trim_string('<div> <span class="foo"/> ok </div>'), $res);
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
        $res = trim_string($tpl->execute());
        $this->assertEquals(trim_string('<div> <span class="foo"/> ok </div>'), $res);
    }
    
}
