<?php

// i18n:domain
//
// The i18n:domain attribute is used to specify the domain to be used to get 
// the translation. If not specified, the translation services will use a 
// default domain. The value of the attribute is used directly; it is not 
// a TALES expression.
// 

/**
 * @package PHPTAL.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Domain extends PHPTAL_Php_Attribute
{
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        // ensure a domain stack exists or create it
        $codewriter->doIf('!isset($__i18n_domains)');
        $codewriter->pushCode('$__i18n_domains = array()');
        $codewriter->doEnd();

        $expression = $codewriter->interpolateTalesVarsInString($this->expression);

        // push current domain and use new domain
        $code = '$__i18n_domains[] = $_translator->useDomain('.$expression.')';
        $codewriter->pushCode($code);
    }

    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
        // restore domain
        $code = '$_translator->useDomain(array_pop($__i18n_domains))';
        $codewriter->pushCode($code);
    }
}

?>
