<?php

require_once 'PHPTAL/Dom/Defs.php';
require_once 'PHPTAL/Namespace.php';

class PHPTAL_Namespace_PHPTAL extends PHPTAL_Namespace
{
    public function __construct()
    {
        parent::__construct('phptal', 'http://xml.zope.org/namespaces/phptal');
        $this->addAttribute(new PHPTAL_NamespaceAttribute('tales', PHPTAL_Dom_Defs::SURROUND, -1));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('debug', PHPTAL_Dom_Defs::SURROUND, -2));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('id', PHPTAL_Dom_Defs::SURROUND, 7));
    }
}

PHPTAL_Dom_Defs::registerNamespace(new PHPTAL_Namespace_PHPTAL());

?>
