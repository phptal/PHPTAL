<?php

require_once 'config.php';
require_once 'PHPTAL.php';
require_once 'PHPTAL/CommentFilter.php';

class CommentFilterTest extends PHPUnit2_Framework_TestCase
{
	function testIt(){
		$t = new PHPTAL('input/comment-filter-01.html');
		$t->setPreFilter(new PHPTAL_CommentFilter());
		$res = $t->execute();
		$res = trim_string($res);
		$exp = trim_file('output/comment-filter-01.html');
		$this->assertEquals($exp,$res);
	}
}


?>
