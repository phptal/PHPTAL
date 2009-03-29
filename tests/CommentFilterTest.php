<?php

require_once PHPTAL_DIR.'PHPTAL/CommentFilter.php';

class CommentFilterTest extends PHPTAL_TestCase
{
	function testIt(){
		$t = $this->newPHPTAL('input/comment-filter-01.html');
		$t->setPreFilter(new PHPTAL_CommentFilter());
		$res = $t->execute();
		$res = trim_string($res);
		$exp = trim_file('output/comment-filter-01.html');
		$this->assertEquals($exp,$res);
	}
}


?>
