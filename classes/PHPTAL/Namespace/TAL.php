<?php

require_once 'PHPTAL/Namespace.php';

class PHPTAL_Namespace_TAL extends PHPTAL_Namespace
{
    public function __construct()
    {
        parent::__construct('tal', 'http://xml.zope.org/namespaces/tal');
        $this->addAttribute(new PHPTAL_NamespaceAttribute('define', PHPTAL_Dom_Defs::SURROUND, 4));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('condition', PHPTAL_Dom_Defs::SURROUND, 6));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('repeat', PHPTAL_Dom_Defs::SURROUND, 8));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('content', PHPTAL_Dom_Defs::CONTENT, 11));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('replace', PHPTAL_Dom_Defs::REPLACE, 9));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('attributes', PHPTAL_Dom_Defs::SURROUND, 9));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('omit-tag', PHPTAL_Dom_Defs::SURROUND, 0));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('comment', PHPTAL_Dom_Defs::SURROUND, 12));
        $this->addAttribute(new PHPTAL_NamespaceAttribute('on-error', PHPTAL_Dom_Defs::SURROUND, 2));
    }
}

PHPTAL_Dom_Defs::registerNamespace(new PHPTAL_Namespace_TAL());

?>
