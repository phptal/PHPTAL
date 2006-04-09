<?php

require_once 'PHPTAL/Filter.php';

class PHPTAL_CommentFilter implements PHPTAL_Filter
{
	public function filter($src){
		return preg_replace('/(<!--.*?-->)/s', '', $src);
	}
}

?>
