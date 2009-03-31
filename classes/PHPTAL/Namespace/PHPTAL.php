<?php

require PHPTAL_DIR.'PHPTAL/Php/Attribute/PHPTAL/Tales.php';
require PHPTAL_DIR.'PHPTAL/Php/Attribute/PHPTAL/Debug.php';
require PHPTAL_DIR.'PHPTAL/Php/Attribute/PHPTAL/Id.php';
require PHPTAL_DIR.'PHPTAL/Php/Attribute/PHPTAL/Cache.php';

/**
 * @package PHPTAL.namespace
 */
class PHPTAL_Namespace_PHPTAL extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('phptal', 'http://xml.zope.org/namespaces/phptal');
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('tales', -1));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('debug', -2));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('id', 7));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('cache', -3));
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_PHPTAL());

?>
