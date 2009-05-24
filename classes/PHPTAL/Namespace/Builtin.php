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

/**
 * @package PHPTAL
 * @subpackage Namespace
 */
class PHPTAL_Namespace_Builtin extends PHPTAL_Namespace
{
    public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_Dom_Element $tag, $expression)
    {
        $name = $att->getLocalName();
        
        // change define-macro to "define macro" and capitalize words
        $name = str_replace(' ', '', ucwords(strtr($name,'-',' ')));

        // case is important when using autoload on case-sensitive filesystems
        $class = 'PHPTAL_Php_Attribute_'.strtoupper($this->getPrefix()).'_'.$name;
        $result = new $class($tag, $expression);
        return $result;
    }
}
