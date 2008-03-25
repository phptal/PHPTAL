<?php

require_once 'config.php';

class EscapeTest extends PHPUnit_Framework_TestCase {

	function testDoesNotEscapeHTMLContent(){
		$tpl = new PHPTAL('input/escape.html');
		$exp = trim_file('output/escape.html');
		$res = trim_string($tpl->execute());
		$this->assertEquals($exp, $res);
	}
}
