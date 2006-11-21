<?php
require_once 'config.php';
require_once 'PHPTAL.php';

class PhptalUsageTest extends PHPUnit_Framework_TestCase 
{

	function testMultiUse(){
		$t = new PHPTAL();
		$t->title = 'hello';
		$t->setTemplate('input/multiuse.01.html');
		$a = $t->execute();
		$t->setTemplate('input/multiuse.02.html');
		$b = $t->execute();
		$this->assertTrue($a != $b, "$a == $b");
	}
}


?>
