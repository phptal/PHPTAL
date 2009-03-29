<?php


class BlockTest extends PHPTAL_TestCase
{
	function testTalBlock(){
		$t = $this->newPHPTAL();
		$t->setSource('<tal:block content="string:content"></tal:block>');
		$res = $t->execute();
		$this->assertEquals('content', $res);
	}

	function testMetalBlock(){
		$t = $this->newPHPTAL();
		$t->setSource('<metal:block>foo</metal:block>');
		$res = $t->execute();
		$this->assertEquals('foo', $res);
	}

	function testSomeNamespaceBlock()
	{
		$t = $this->newPHPTAL();
		$t->setSource('<foo:block xmlns:foo="http://phptal.example.com">foo</foo:block>');
		$res = $t->execute();
		$this->assertEquals('<foo:block xmlns:foo="http://phptal.example.com">foo</foo:block>', $res);
	}
	
	/**
     * @expectedException PHPTAL_ParserException
     */    
	function testInvalidNamespaceBlock()
	{
		$t = $this->newPHPTAL();
				
		$this->setExpectedException('PHPTAL_Exception');
		
		$t->setSource('<foo:block>foo</foo:block>');		
		$res = $t->execute();		
	}	
}


