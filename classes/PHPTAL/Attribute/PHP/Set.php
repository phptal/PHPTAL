<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_PHP_Set extends PHPTAL_Attribute
{
    public function start( $tag, $gen )
    {
        if (strpos($this->expression, '=') === false) {
            $gen->obStart();
            $tag->generateContent( $gen );
            $gen->obGetContents( $this->expression );
            $gen->obClean();
        }
        else {
            $gen->addLine($this->expression);
        }
    }
    
    public function end( $tag, $gen )
    {
    }
}

?>
