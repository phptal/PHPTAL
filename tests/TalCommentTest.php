<?php

require_once 'config.php';
require_once 'PHPTAL/Parser.php';
require_once 'PHPTAL/CodeGenerator.php';
require_once 'PHPTAL/Attribute/TAL/Comment.php';

if (!class_exists('DummyTag')) {
    class DummyTag {}
}

class TalCommentTest extends PHPUnit2_Framework_TestCase 
{
    function setUp()
    {
        $this->_gen = new PHPTAL_CodeGenerator();
        $this->_tag = new DummyTag();
        $this->_tag->generator = $this->_gen;
    }
    
    function testComment()
    {
        $att = PHPTAL_Attribute::createAttribute($this->_tag, 'tal:comment', 'my dummy comment');
        $att->start();
        $att->end();
        $res = $this->_gen->getResult();
        $this->assertEquals('<?php /* my dummy comment */; ?>', $res);
    }

    function testMultiLineComment()
    {
        $comment = "my dummy comment\non more than one\nline";
        $att = PHPTAL_Attribute::createAttribute($this->_tag, 'tal:comment', $comment);
        $att->start();
        $att->end();
        $res = $this->_gen->getResult();
        $this->assertEquals("<?php /* $comment */; ?>", $res);
    }

    function testTrickyComment()
    {
        $comment = "my dummy */ comment\non more than one\nline";
        $att = PHPTAL_Attribute::createAttribute($this->_tag, 'tal:comment', $comment);
        $att->start();
        $att->end();
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
}
        
?>
