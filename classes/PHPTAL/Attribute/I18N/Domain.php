<?php

// i18n:domain
//
// The i18n:domain attribute is used to specify the domain to be used to get 
// the translation. If not specified, the translation services will use a 
// default domain. The value of the attribute is used directly; it is not 
// a TALES expression.
// 
class PHPTAL_Attribute_I18N_Domain extends PHPTAL_Attribute
{
    function start()
    {
        $this->tag->generator->doIf('not isset($__i18n_domains)');
        $this->tag->generator->pushCode('$__i18n_domains = array()');
        $this->tag->generator->end();
        
        $code = '$__i18n_domains[] = $tpl->getTranslator()->setDomain(\'%s\')';
        $code = sprintf($code, $this->expression);
        $this->tag->generator->pushCode($code);
    }

    function end()
    {
        $this->tag->generator->pushCode('$tpl->getTranslator()->setDomain(array_pop($__i18n_domains))');
    }
}

?>
