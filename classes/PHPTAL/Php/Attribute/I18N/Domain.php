<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// i18n:domain
//
// The i18n:domain attribute is used to specify the domain to be used to get 
// the translation. If not specified, the translation services will use a 
// default domain. The value of the attribute is used directly; it is not 
// a TALES expression.
// 

/**
 * @package phptal.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Domain extends PHPTAL_Php_Attribute
{
    public function start()
    {
        // ensure a domain stack exists or create it
        $this->tag->generator->doIf('!isset($__i18n_domains)');
        $this->tag->generator->pushCode('$__i18n_domains = array()');
        $this->tag->generator->doEnd();

        //\''.str_replace(array('\\',"'"),array('\\\\',"\\'"),$expression).'\'
        $expression = $this->tag->generator->interpolateTalesVarsInString($this->expression);

        // push current domain and use new domain
        $code = '$__i18n_domains[] = $_translator->useDomain('.$expression.')';
        $this->tag->generator->pushCode($code);
    }

    public function end()
    {
        // restore domain
        $code = '$_translator->useDomain(array_pop($__i18n_domains))';
        $this->tag->generator->pushCode($code);
    }
}

?>
