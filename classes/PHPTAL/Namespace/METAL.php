<?php

require_once 'PHPTAL/Dom/Defs.php';
require_once 'PHPTAL/Namespace.php';

class PHPTAL_Namespace_METAL extends PHPTAL_Namespace
{
    public function __construct()
    {
        parent::__construct('metal', 'http://xml.zope.org/namespaces/metal');
        $this->addAttribute(new PHPTAL_NamespaceAttribute('define-macro', PHPTAL_Defs::SURROUND, 1));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('use-macro', PHPTAL_Defs::REPLACE, 9));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('define-slot', PHPTAL_Defs::SURROUND, 9));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('fill-slot', PHPTAL_Defs::SURROUND, 9));
    }
}

PHPTAL_Defs::registerNamespace(new PHPTAL_Namespace_METAL());

?>
