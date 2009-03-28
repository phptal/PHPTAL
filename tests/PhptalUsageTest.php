<?php
require_once 'config.php';

class PhptalUsageTest extends PHPTAL_TestCase 
{
	function testMultiUse()
	{
		$t = $this->newPHPTAL();
		$t->title = 'hello';
		$t->setTemplate('input/multiuse.01.html');
		$a = $t->execute();
		$t->setTemplate('input/multiuse.02.html');
		$b = $t->execute();
		$this->assertTrue($a != $b, "$a == $b");
		$this->assertContains('hello',$a);
		$this->assertContains('hello',$b);
	}
		
    function testSetSourceReset()
    {
        $t = $this->newPHPTAL();        
        $t->setSource('<p>Hello</p>');
        $res1 = $t->execute();
        $t->setSource('<p>World</p>');
        $res2 = $t->execute();
        
        $this->assertNotEquals($res1,$res2);
    }
}

