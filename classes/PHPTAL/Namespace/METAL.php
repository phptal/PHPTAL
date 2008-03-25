<?php

require_once PHPTAL_DIR.'PHPTAL/Dom/Defs.php';
require_once PHPTAL_DIR.'PHPTAL/Namespace.php';

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/METAL/DefineMacro.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/METAL/UseMacro.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/METAL/DefineSlot.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/METAL/FillSlot.php';

/** 
 * @package phptal.namespace
 */
class PHPTAL_Namespace_METAL extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('metal', 'http://xml.zope.org/namespaces/metal');
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('define-macro', 1));
        $this->addAttribute(new PHPTAL_NamespaceAttributeReplace('use-macro', 9));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('define-slot', 9));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('fill-slot', 9));
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_METAL());

?>
