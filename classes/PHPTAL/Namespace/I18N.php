<?php

require_once 'PHPTAL/Dom/Defs.php';
require_once 'PHPTAL/Namespace.php';

class PHPTAL_Namespace_I18N extends PHPTAL_Namespace
{
    public function __construct()
    {
        parent::__construct('i18n', 'http://xml.zope.org/namespaces/i18n');
        $this->addAttribute(new PHPTAL_NamespaceAttribute('translate', PHPTAL_Dom_Defs::CONTENT, 5));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('name', PHPTAL_Dom_Defs::SURROUND, 5));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('attributes', PHPTAL_Dom_Defs::SURROUND, 10));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('domain', PHPTAL_Dom_Defs::SURROUND, 3));
    }
}

PHPTAL_Dom_Defs::registerNamespace(new PHPTAL_Namespace_I18N());

?>
