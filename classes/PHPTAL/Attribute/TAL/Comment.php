<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_Comment extends PHPTAL_Attribute
{
    public function start()
    {
        $this->tag->generator->doComment( $this->expression );
    }

    public function end(){}
}

?>
