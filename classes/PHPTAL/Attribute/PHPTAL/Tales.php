<?php

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_PHPTAL_TALES extends PHPTAL_Attribute
{
    function start()
    {
        $mode = trim($this->expression);
        $mode = strtolower($mode);
        
        if ($mode == '' || $mode == 'default') 
            $mode = 'tales';
        
        if ($mode != 'php' && $mode != 'tales') {
            $err = "Unsuppported TALES mode %s";
            $err = sprintf($err, $mode);
            throw new Exception($err);            
        }
        
        $this->_oldMode = $this->tag->generator->setTalesMode( $mode );
    }

    function end()
    {
        $this->tag->generator->setTalesMode( $this->_oldMode );
    }

    private $_oldMode;
}

?>
