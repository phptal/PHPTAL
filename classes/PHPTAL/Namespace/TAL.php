<?php

require_once PHPTAL_DIR.'PHPTAL/Namespace.php';

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Comment.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Replace.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Content.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Condition.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Attributes.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Repeat.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/Define.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/OnError.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute/TAL/OmitTag.php';

/**
 * @package phptal.namespace
 */
class PHPTAL_Namespace_TAL extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('tal', 'http://xml.zope.org/namespaces/tal');
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('define', 4));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('condition', 6));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('repeat', 8));
        $this->addAttribute(new PHPTAL_NamespaceAttributeContent('content', 11));
        $this->addAttribute(new PHPTAL_NamespaceAttributeReplace('replace', 9));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('attributes', 9));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('omit-tag', 0));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('comment', 12));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('on-error', 2));
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_TAL());

?>
