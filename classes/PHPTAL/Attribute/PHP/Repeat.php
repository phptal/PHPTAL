<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_PHP_Repeat extends PHPTAL_Attribute
{
    public function start( $tag, $gen )
    {
        $gen->addLine('foreach (' . $this->expression . ') {');
    }
    
    public function end( $tag, $gen )
    {
        $gen->addLine('}');
    }
}

?>
