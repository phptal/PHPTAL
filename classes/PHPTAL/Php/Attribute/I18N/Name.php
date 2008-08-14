<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// i18n:name
//
// Name the content of the current element for use in interpolation within 
// translated content. This allows a replaceable component in content to be
// re-ordered by translation. For example:
//
// <span i18n:translate=''>
//   <span tal:replace='here/name' i18n:name='name' /> was born in
//   <span tal:replace='here/country_of_birth' i18n:name='country' />.
// </span>
//
// would cause this text to be passed to the translation service:
//
//     "${name} was born in ${country}."
//     

/**
 * @package phptal.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Name extends PHPTAL_Php_Attribute
{
    public function start()
    {
        $this->tag->generator->pushCode('ob_start()');
    }

    public function end()
    {
        $code = '$_translator->setVar(\'%s\', ob_get_contents())';
        $code = sprintf($code, $this->expression);
        $this->tag->generator->pushCode($code);
        $this->tag->generator->pushCode('ob_end_clean()');
    }
}

?>
