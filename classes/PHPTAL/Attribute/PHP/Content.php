<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_PHP_Content extends PHPTAL_Attribute
{
    public function start( $tag, $gen )
    {
        if (trim($this->expression) != "")
            $gen->doPrint($this->expression);
    }
    
    public function end( $tag, $gen ){}
}

?>
