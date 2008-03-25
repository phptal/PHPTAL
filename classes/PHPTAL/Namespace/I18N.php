<?php

require_once PHPTAL_DIR.'PHPTAL/Dom/Defs.php';
require_once PHPTAL_DIR.'PHPTAL/Namespace.php';

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/I18N/Translate.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/I18N/Name.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/I18N/Domain.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/I18N/Attributes.php';

/**
 * @package phptal.namespace
 */
class PHPTAL_Namespace_I18N extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('i18n', 'http://xml.zope.org/namespaces/i18n');
        $this->addAttribute(new PHPTAL_NamespaceAttributeContent('translate', 5));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('name', 5));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('attributes', 10));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('domain', 3));
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_I18N());

?>
