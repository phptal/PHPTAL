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
require_once PHPTAL_DIR.'PHPTAL/Dom/DocumentBuilder.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Node.php';
require_once PHPTAL_DIR.'PHPTAL/Php/State.php';
require_once PHPTAL_DIR.'PHPTAL/Php/CodeWriter.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Comment.php';

if (!class_exists('DummyPhpNode')) {
    class DummyPhpNode extends PHPTAL_DOMElement {
        function __construct() {}
        function generate(PHPTAL_Php_CodeWriter $codewriter) {}
    }
}

class TalCommentTest extends PHPTAL_TestCase 
{
    function setUp()
    {
        parent::setUp();
        $state = new PHPTAL_Php_State();
        $this->_gen = new PHPTAL_Php_CodeWriter($state);
        $this->_tag = new DummyPhpNode();
        $this->_tag->codewriter = $this->_gen;
    }
    
    private function newComment($expr)
    {
        return $this->_att = new PHPTAL_Php_Attribute_TAL_Comment($this->_tag, $expr);
    }
    
    function testComment()
    {
        $this->newComment( 'my dummy comment');
        $this->_att->start($this->_gen);
        $this->_att->end($this->_gen);
        $res = $this->_gen->getResult();
        $this->assertEquals('<?php /* my dummy comment */; ?>', $res);
    }

    function testMultiLineComment()
    {
        $comment = "my dummy comment\non more than one\nline";
        $this->newComment($comment);
        $this->_att->start($this->_gen);
        $this->_att->end($this->_gen);
        $res = $this->_gen->getResult();
        $this->assertEquals("<?php /* $comment */; ?>", $res);
    }

    function testTrickyComment()
    {
        $comment = "my dummy */ comment\non more than one\nline";
        $this->newComment(  $comment);
        $this->_att->start($this->_gen);
        $this->_att->end($this->_gen);
        $res = $this->_gen->getResult();
        $comment = str_replace('*/', '* /', $comment);
        $this->assertEquals("<?php /* $comment */; ?>", $res);
    }

    function testInTemplate()
    {
        $tpl = new PHPTAL('input/tal-comment.01.html'); 
        $res = trim_string($tpl->execute()); 
        $exp = trim_file('output/tal-comment.01.html');
        $this->assertEquals($exp, $res);
    }

    function testMultilineInTemplate()
    {
        $tpl = new PHPTAL('input/tal-comment.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-comment.02.html');
        $this->assertEquals($exp, $res);
    }

    private $_tag;
    private $_gen;
    private $_att;
}
        
?>
