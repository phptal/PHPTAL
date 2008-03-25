<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// i18n:target
//
// The i18n:target attribute specifies the language of the translation we 
// want to get. If the value is "default", the language negotiation services 
// will be used to choose the destination language. If the value is 
// "nothing", no translation will be performed; this can be used to suppress 
// translation within a larger translated unit. Any other value must be a 
// language code.
//
// The attribute value is a TALES expression; the result of evaluating the 
// expression is the language code or one of the reserved values.
//
// Note that i18n:target is primarily used for hints to text extraction 
// tools and translation teams. If you had some text that should only be 
// translated to e.g. German, then it probably shouldn't be wrapped in an 
// i18n:translate span.
// 

/**
 * @package phptal.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Target extends PHPTAL_Php_Attribute
{
    public function start(){}
    public function end(){}
}

?>
