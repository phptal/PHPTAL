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
 * @link     http://phptal.motion-twin.com/ 
 */
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

/**
 * PHPTAL constants.
 * 
 * This is a pseudo singleton class, a user may decide to provide 
 * his own singleton instance which will then be used by PHPTAL.
 *
 * This behaviour is mainly useful to remove builtin namespaces 
 * and provide custom ones.
 * 
 * @package PHPTAL.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Defs
{
    /**
     * this is a singleton
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new PHPTAL_Dom_Defs();
        }
        return self::$_instance;
    }
    
    /**
     * @param string $tagName local name of the tag
     * @return true if it's empty in XHTML (e.g. <img/>)
     */
    public function isEmptyTag($tagName)
    {
        return in_array(strtolower($tagName), self::$XHTML_EMPTY_TAGS);
    }

    /**
     * gives namespace URI for given registered (built-in) prefix
     */
    public function prefixToNamespaceURI($prefix)
    {
        return isset($this->prefix_to_uri[$prefix]) ? $this->prefix_to_uri[$prefix] : false;
    }
    
    /**
     * gives typical prefix for given (built-in) namespace
     */
    public function namespaceURIToPrefix($uri)
    {
        return array_search($uri, $this->prefix_to_uri,true);
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
     * true if elements content is parsed as CDATA in text/html
     */
    public function isCDATAElementInHTML($namespace_uri, $local_name)
    {
        return ($local_name === 'script' || $local_name === 'style') 
            && ($namespace_uri === 'http://www.w3.org/1999/xhtml' || $namespace_uri === '');            
    }
    
    /**
     * Returns true if the attribute is a valid phptal attribute 
     *
     * Examples of valid attributes: tal:content, metal:use-slot
     * Examples of invalid attributes: tal:unknown, metal:content
     *
     * @return bool
     */
    public function isValidAttributeNS($namespace_uri, $local_name)
    {
        if (!$this->isHandledNamespace($namespace_uri)) return false;
               
        $attrs = $this->namespaces_by_uri[$namespace_uri]->getAttributes();
        return isset($attrs[$local_name]);
    }
    
    /**
     * is URI registered (built-in) namespace
     */
    public function isHandledNamespace($namespace_uri)
    {
        return isset($this->namespaces_by_uri[$namespace_uri]);
    }

    /**
     * Returns true if the attribute is a phptal handled xml namespace
     * declaration.
     *
     * Examples of handled xmlns:  xmlns:tal, xmlns:metal
     *
     * @return bool
     */
    public function isHandledXmlNs($qname, $value)
    {
        return substr(strtolower($qname), 0, 6) == 'xmlns:' && $this->isHandledNamespace($value);
    }
    
    /**
     * return objects that holds information about given TAL attribute
     */
    public function getNamespaceAttribute($namespace_uri, $local_name)
    {    
        $attrs = $this->namespaces_by_uri[$namespace_uri]->getAttributes();
        return $attrs[$local_name];
    }

    /**
     * Register a PHPTAL_Namespace and its attribute into PHPTAL.
     */
    public function registerNamespace(PHPTAL_Namespace $ns)
    {
        $prefix = strtolower($ns->getPrefix());
        $this->_namespaces[$prefix] = $ns;
        $this->namespaces_by_uri[$ns->getNamespaceURI()] = $ns;
        $this->_xmlns[$ns->getNamespaceURI()] = $prefix;
        $this->prefix_to_uri[$ns->getPrefix()] = $ns->getNamespaceURI();
        foreach ($ns->getAttributes() as $name => $attribute) {
            $key = $prefix.':'.strtolower($name);
            $this->_dictionary[$key] = $attribute;
        }
    }
    
    private static $_instance = null;
    private $_dictionary = array();
    private $_namespaces = array(), $namespaces_by_uri = array();
    private $_xmlns = array();
    private $prefix_to_uri = array();

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
