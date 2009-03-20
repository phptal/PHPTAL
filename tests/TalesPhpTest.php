<?php

require_once 'config.php';

class TalesPhpTest extends PHPTAL_TestCase {
	
	function testMix(){
		$tpl = new PHPTAL('input/php.html');
		$tpl->real = 'real value';
		$tpl->foo = 'real';
		$res = trim_string($tpl->execute());
		$exp = trim_file('output/php.html');
		$this->assertEquals($exp,$res);
	}
}

?>
