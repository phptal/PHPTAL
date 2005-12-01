<?php

require_once 'PHPTAL/Dom/Defs.php';
require_once 'PHPTAL/Namespace.php';

require_once 'PHPTAL/Php/Attribute/PHPTAL/Tales.php';
require_once 'PHPTAL/Php/Attribute/PHPTAL/Debug.php';
require_once 'PHPTAL/Php/Attribute/PHPTAL/Id.php';

/**
 * @package phptal.namespace
 */
class PHPTAL_Namespace_PHPTAL extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('phptal', 'http://xml.zope.org/namespaces/phptal');
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('tales', -1));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('debug', -2));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('id', 7));
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_PHPTAL());

?>
