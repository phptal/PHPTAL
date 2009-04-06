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
require PHPTAL_DIR.'PHPTAL/Php/Attribute.php';
require PHPTAL_DIR.'PHPTAL/Namespace/TAL.php';
require PHPTAL_DIR.'PHPTAL/Namespace/METAL.php';
require PHPTAL_DIR.'PHPTAL/Namespace/I18N.php';
require PHPTAL_DIR.'PHPTAL/Namespace/PHPTAL.php';


/** 
 * Information about TAL attributes (in which order they are executed and how they generate the code)
 *
 * From http://dev.zope.org/Wikis/DevSite/Projects/ZPT/TAL%20Specification%201.4
 *
 * Order of Operations
 *
 * When there is only one TAL statement per element, the order in which
 * they are executed is simple. Starting with the root element, each
 * element's statements are executed, then each of its child elements is
 * visited, in order, to do the same.
 * 
 * Any combination of statements may appear on the same elements, except
 * that the content and replace statements may not appear together.
 * 
 * When an element has multiple statements, they are executed in this
 * order:
 * 
 *     * define
 *     * condition
 *     * repeat
 *     * content or replace
 *     * attributes
 *     * omit-tag
 * 
 * Since the on-error statement is only invoked when an error occurs, it
 * does not appear in the list.
 * 
 * The reasoning behind this ordering goes like this: You often want to set
 * up variables for use in other statements, so define comes first. The
 * very next thing to do is decide whether this element will be included at
 * all, so condition is next; since the condition may depend on variables
 * you just set, it comes after define. It is valuable be able to replace
 * various parts of an element with different values on each iteration of a
 * repeat, so repeat is next. It makes no sense to replace attributes and
 * then throw them away, so attributes is last. The remaining statements
 * clash, because they each replace or edit the statement element.
 * 
 * If you want to override this ordering, you must do so by enclosing the
 * element in another element, possibly div or span, and placing some of
 * the statements on this new element. 
 *
 * 
 * @package PHPTAL.namespace
 */
abstract class PHPTAL_NamespaceAttribute
{
    /** 
     * @param string $name The attribute name
     * @param int $priority Attribute execution priority
     */
    public function __construct($local_name, $priority)
    {
        $this->local_name = $local_name;
        $this->_priority = $priority;
    }

    /**
     * @return string
     */
    public function getLocalName()
    { 
        return $this->local_name; 
    }
    
    public function getPriority(){ return $this->_priority; }
    public function getNamespace(){ return $this->_namespace; }
    public function setNamespace(PHPTAL_Namespace $ns){ $this->_namespace = $ns; }

    public function createAttributeHandler(PHPTAL_DOMElement $tag, $expression)
    {
        return $this->_namespace->createAttributeHandler($this, $tag, $expression);
    }
    
    /** Attribute name without the namespace: prefix */
    private $local_name;
    
    /** [0 - 1000] */         
    private $_priority;     
    
    /** PHPTAL_Namespace */
    private $_namespace;    
}

/** 
 * This type of attribute wraps element
 * @package PHPTAL.namespace
 */
class PHPTAL_NamespaceAttributeSurround extends PHPTAL_NamespaceAttribute 
{
}

/** 
 * This type of attribute replaces element entirely
 * @package PHPTAL.namespace
 */
class PHPTAL_NamespaceAttributeReplace extends PHPTAL_NamespaceAttribute 
{
}

/** 
 * This type of attribute replaces element's content entirely
 * @package PHPTAL.namespace
 */
class PHPTAL_NamespaceAttributeContent extends PHPTAL_NamespaceAttribute 
{
}

/** 
 * @package PHPTAL.namespace
 */
abstract class PHPTAL_Namespace
{   
    private $prefix, $namespace_uri;

    public function __construct($prefix, $namespace_uri)
    {
        $this->_attributes = array();
        $this->prefix = $prefix;
        $this->namespace_uri = $namespace_uri;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getNamespaceURI()
    {
        return $this->namespace_uri;
    }

    public function hasAttribute($attributeName)
    {
        return array_key_exists(strtolower($attributeName), $this->_attributes);
    }

    public function getAttribute($attributeName)
    {
        return $this->_attributes[strtolower($attributeName)];
    }
    
    public function addAttribute(PHPTAL_NamespaceAttribute $attribute)
    {
        $attribute->setNamespace($this);
        $this->_attributes[strtolower($attribute->getLocalName())] = $attribute;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    abstract public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_DOMElement $tag, $expression);

    protected $_attributes;
}

/** 
 * @package PHPTAL.namespace
 */
class PHPTAL_BuiltinNamespace extends PHPTAL_Namespace
{
    public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_DOMElement $tag, $expression)
    {
        $name = $att->getLocalName();
        $name = str_replace('-', '', $name);
        
        $class = 'PHPTAL_Php_Attribute_'.$this->getPrefix().'_'.$name;
        $result = new $class($tag, $expression);
        return $result;
    }
}

