<?php

require_once 'PHPTAL/Parser/Defs.php';
require_once 'PHPTAL/Namespace.php';

class PHPTAL_Namespace_I18N extends PHPTAL_Namespace
{
    public function __construct()
    {
        parent::__construct('i18n', 'http://xml.zope.org/namespaces/i18n');
        $this->addAttribute(new PHPTAL_NamespaceAttribute('translate', PHPTAL_Defs::CONTENT, 5));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('name', PHPTAL_Defs::SURROUND, 5));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('attributes', PHPTAL_Defs::SURROUND, 10));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('domain', PHPTAL_Defs::SURROUND, 3));
    }
}

PHPTAL_Defs::registerNamespace(new PHPTAL_Namespace_I18N());

?>
