<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_PHP_Replace extends PHPTAL_Attribute
{
    public function start( $tag, $gen )
    {
        if (strlen(trim($this->expression)) != 0)
            $gen->doPrint( $this->expression );
    }
    
    public function end( $tag, $gen )
    {
    }
}

?>
