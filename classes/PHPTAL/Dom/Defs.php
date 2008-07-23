<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

// From http://dev.zope.org/Wikis/DevSite/Projects/ZPT/TAL%20Specification%201.4
//
// Order of Operations
//
// When there is only one TAL statement per element, the order in which
// they are executed is simple. Starting with the root element, each
// element's statements are executed, then each of its child elements is
// visited, in order, to do the same.
// 
// Any combination of statements may appear on the same elements, except
// that the content and replace statements may not appear together.
// 
// When an element has multiple statements, they are executed in this
// order:
// 
//     * define
//     * condition
//     * repeat
//     * content or replace
//     * attributes
//     * omit-tag
// 
// Since the on-error statement is only invoked when an error occurs, it
// does not appear in the list.
// 
// The reasoning behind this ordering goes like this: You often want to set
// up variables for use in other statements, so define comes first. The
// very next thing to do is decide whether this element will be included at
// all, so condition is next; since the condition may depend on variables
// you just set, it comes after define. It is valuable be able to replace
// various parts of an element with different values on each iteration of a
// repeat, so repeat is next. It makes no sense to replace attributes and
// then throw them away, so attributes is last. The remaining statements
// clash, because they each replace or edit the statement element.
// 
// If you want to override this ordering, you must do so by enclosing the
// element in another element, possibly div or span, and placing some of
// the statements on this new element. 
// 

require_once PHPTAL_DIR.'PHPTAL/Namespace.php';
require_once PHPTAL_DIR.'PHPTAL/Namespace/TAL.php';
require_once PHPTAL_DIR.'PHPTAL/Namespace/METAL.php';
require_once PHPTAL_DIR.'PHPTAL/Namespace/I18N.php';
require_once PHPTAL_DIR.'PHPTAL/Namespace/PHPTAL.php';

/**
 * PHPTAL constants.
 * 
 * This is a pseudo singleton class, a user may decide to provide 
 * his own singleton instance which will then be used by PHPTAL.
 *
 * This behaviour is mainly usefull to remove builtin namespaces 
 * and provide custom ones.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Defs
{
    public static function getInstance()
    {
        if (self::$_instance == null){
            self::$_instance = new PHPTAL_Dom_Defs();
        }
        return self::$_instance;
    }

    public static function setInstance(PHPTAL_Dom_Defs $instance)
    {
        self::$_instance = $instance;
    }

    
    public function __construct()
    {
        $this->_dictionary = array();
        $this->_namespaces = array();
        $this->_xmlns = array();
    }
    
    public function isEmptyTag($tagName)
    {
        return in_array(strtolower($tagName), self::$XHTML_EMPTY_TAGS);
    }

    public function xmlnsToLocalName($xmlns)
    {
        return $this->_xmlns[$xmlns];
    }
    
    /**
     * Returns true if the attribute is an xhtml boolean attribute.
     *
     * @return bool
     */
    public function isBooleanAttribute($att)
    {
        return in_array($att, self::$XHTML_BOOLEAN_ATTRIBUTES);
    }

    /**
     * Returns true if the attribute is in the phptal dictionnary.
     *
     * @return bool
     */
    public function isPhpTalAttribute($att)
    {
        return array_key_exists(strtolower($att), $this->_dictionary);
    }
    
    /**
     * Returns true if the attribute is a valid phptal attribute or an unknown
     * attribute.
     *
     * Examples of valid attributes: tal:content, metal:use-slot
     * Examples of invalid attributes: tal:unknown, metal:content
     *
     * @return bool
     */
    public function isValidAttribute($att)
    {
        if (preg_match('/^(.*):(.*)$/', $att, $m)) {
            list (,$ns,$sub) = $m;
            if (array_key_exists(strtolower($ns), $this->_namespaces)
                && !$this->isPhpTalAttribute($att)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if the attribute is a phptal handled xml namespace
     * declaration.
     *
     * Examples of handled xmlns:  xmlns:tal, xmlns:metal
     *
     * @return bool
     */
    public function isHandledXmlNs($att, $value)
    {
        $att = strtolower($att);
        return substr($att, 0, 6) == 'xmlns:'
            && array_key_exists($value, $this->_xmlns);
    }

    public function getNamespaceAttribute($attName)
    {
        return $this->_dictionary[strtolower($attName)];
    }

    /**
     * Register a PHPTAL_Namespace and its attribute into PHPTAL.
     */
    public function registerNamespace(PHPTAL_Namespace $ns)
    {
        $nsname = strtolower($ns->name);
        $this->_namespaces[$nsname] = $ns;
        $this->_xmlns[$ns->xmlns] = $nsname;
        foreach ($ns->getAttributes() as $name => $attribute){
            $key = $nsname.':'.strtolower($name);
            $this->_dictionary[$key] = $attribute;
        }
    }

    private static $_instance = null;
    private $_dictionary;
    private $_namespaces;
    private $_xmlns;

    /**
     * This array contains XHTML tags that must be echoed in a &lt;tag/&gt; form
     * instead of the &lt;tag&gt;&lt;/tag&gt; form.
     *
     * In fact, some browsers does not support the later form so PHPTAL 
     * ensure these tags are correctly echoed.
     */
    private static $XHTML_EMPTY_TAGS = array(
        'area',
        'base',
        'basefont',
        'br',
        'col',
        'frame',
        'hr',
        'img',
        'input',
        'isindex',
        'link',
        'meta',
        'param',
    );

    /**
     * This array contains XHTML boolean attributes, their value is self 
     * contained (ie: they are present or not).
     */
    private static $XHTML_BOOLEAN_ATTRIBUTES = array(
        'checked',
        'compact',
        'declare',
        'defer',
        'disabled',
        'ismap',
        'multiple',
        'noresize',
        'noshade',
        'nowrap',
        'readonly',
        'selected',
    );
}

?>
