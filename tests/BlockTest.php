<?php

require_once 'config.php';

class BlockTest extends PHPTAL_TestCase
{
	function testTalBlock(){
		$t = new PHPTAL();
		$t->setSource('<tal:block content="string:content"></tal:block>');
		$res = $t->execute();
		$this->assertEquals('content', $res);
	}

	function testMetalBlock(){
		$t = new PHPTAL();
		$t->setSource('<metal:block>foo</metal:block>');
		$res = $t->execute();
		$this->assertEquals('foo', $res);
	}

	function testSomeNamespaceBlock()
	{
		$t = new PHPTAL();
		$t->setSource('<foo:block xmlns:foo="http://phptal.example.com">foo</foo:block>');
		$res = $t->execute();
		$this->assertEquals('<foo:block xmlns:foo="http://phptal.example.com">foo</foo:block>', $res);
	}
	
	/**
     * @expectedException PHPTAL_ParserException
     */    
	function testInvalidNamespaceBlock()
	{
		$t = new PHPTAL();
				
		$this->setExpectedException('PHPTAL_Exception');
		
		$t->setSource('<foo:block>foo</foo:block>');		
		$res = $t->execute();		
	}	
}


