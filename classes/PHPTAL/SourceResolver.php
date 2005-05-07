<?php

require_once 'PHPTAL/Source.php';

interface PHPTAL_SourceResolver 
{
    /**
     * Returns PHPTAL_Source or null.
     */
    public function resolve($path);
}

?>
