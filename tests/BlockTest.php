<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class BlockTest extends PHPUnit_Framework_TestCase
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

	function testUnknownNamespaceBlock(){
		$t = new PHPTAL();
		$t->setSource('<foo:block>foo</foo:block>');
		$res = $t->execute();
		$this->assertEquals('<foo:block>foo</foo:block>', $res);
	}
}

?>
