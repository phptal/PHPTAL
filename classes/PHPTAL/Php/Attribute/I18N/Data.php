<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// i18n:data
//
// Since TAL always returns strings, we need a way in ZPT to translate 
// objects, the most obvious case being DateTime objects. The data attribute 
// will allow us to specify such an object, and i18n:translate will provide 
// us with a legal format string for that object. If data is used, 
// i18n:translate must be used to give an explicit message ID, rather than 
// relying on a message ID computed from the content.
// 

/**
 * @package phptal.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Data extends PHPTAL_Php_Attribute
{
    public function start(){}
    public function end(){}
}

?>
