<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// i18n:source
//
// The i18n:source attribute specifies the language of the text to be 
// translated. The default is "nothing", which means we don't provide 
// this information to the translation services.
//

/**
 * @package phptal.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Source extends PHPTAL_Php_Attribute
{
    public function start()
    {
        // ensure that a sources stack exists or create it
        $this->tag->generator->doIf('!isset($__i18n_sources)');
        $this->tag->generator->pushCode('$__i18n_sources = array()');
        $this->tag->generator->end();

        // push current source and use new one
        $code = '$__i18n_sources[] = $_translator->setSource(\'%s\')';
        $code = sprintf($code, $this->expression);
        $this->tag->generator->pushCode($code);
    }

    public function end()
    {
        // restore source
        $code = '$_translator->setSource(array_pop($__i18n_sources))';
        $this->tag->generator->pushCode($code);
    }
}

?>
