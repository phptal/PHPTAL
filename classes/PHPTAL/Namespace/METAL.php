<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */

require 'PHPTAL/Php/Attribute/METAL/DefineMacro.php';
require 'PHPTAL/Php/Attribute/METAL/UseMacro.php';
require 'PHPTAL/Php/Attribute/METAL/DefineSlot.php';
require 'PHPTAL/Php/Attribute/METAL/FillSlot.php';

/**
 * @package PHPTAL.namespace
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

