<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_PHP_Condition extends PHPTAL_Attribute
{
    public function start( $tag, $gen )
    {
        $gen->addLine('if (' . $this->expression . ') {' );
    }

    public function end( $tag, $gen ) 
    {
        $gen->addLine('}');
    }
}

