<?php

/**
 * @package PHPTAL
 */
abstract class PHPTAL_Attribute
{
    protected $tag;
    
    public function __construct( $tag )
    {
        $this->tag = $tag;
    }
    
    public abstract function start(){}
    public abstract function end(){}
}

?>
