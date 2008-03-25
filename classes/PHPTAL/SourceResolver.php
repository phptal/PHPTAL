<?php

require_once PHPTAL_DIR.'PHPTAL/Source.php';

/** 
 * @package phptal
 */
interface PHPTAL_SourceResolver 
{
    /**
     * Returns PHPTAL_Source or null.
     */
    public function resolve($path);
}

?>
