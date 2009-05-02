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
 * This class handles template execution context.
 * Holds template variables and carries state/scope across macro executions.
 *
 */
class PHPTAL_Context
{
    public $_line = false;
    public $_file = false;
    public $repeat;
    public $_xmlDeclaration;
    public $_docType;
    private $_nothrow;

    public function __construct()
    {
        $this->repeat = new StdClass();
    }

    public function __clone()
    {
        $this->repeat = clone($this->repeat);
    }

    /**
     * will switch to this context when popContext() is called
     *
     * @return void
     */
    public function setParent(PHPTAL_Context $parent)
    {
        $this->_parentContext = $parent;
    }

    /**
     * set StdClass object which has property of every global variable
     * It can use __isset() and __get() [none of them or both]
     *
     * @return void
     */
    public function setGlobal(StdClass $globalContext)
    {
        $this->_globalContext = $globalContext;
    }

    /**
     * save current execution context
     *
     * @return Context (new)
     */
    public function pushContext()
    {
        $res = clone $this;
        $res->setParent($this);
        return $res;
    }

    /**
     * get previously saved execution context
     *
     * @return Context (old)
     */
    public function popContext()
    {
        return $this->_parentContext;
    }

    /**
     * Set output document type if not already set.
     *
     * This method ensure PHPTAL uses the first DOCTYPE encountered (main
     * template or any macro template source containing a DOCTYPE.
     *
     * @return void
     */
    public function setDocType($doctype)
    {
        if ($this->_parentContext) {
            return $this->_parentContext->setDocType($doctype);
        }
        if ($this->_parentContext) {
            return $this->_parentContext->setDocType($doctype);
        }
        if (!$this->_docType) {
            $this->_docType = $doctype;
        }
    }

    /**
     * Set output document xml declaration.
     *
     * This method ensure PHPTAL uses the first xml declaration encountered
     * (main template or any macro template source containing an xml
     * declaration)
     *
     * @return void
     */
    public function setXmlDeclaration($xmldec)
    {
        if ($this->_parentContext) {
            return $this->_parentContext->setXmlDeclaration($xmldec);
        }
        if ($this->_parentContext) {
            return $this->_parentContext->setXmlDeclaration($xmldec);
        }
        if (!$this->_xmlDeclaration) {
            $this->_xmlDeclaration = $xmldec;
        }
    }

    /**
     * Activate or deactivate exception throwing during unknown path
     * resolution.
     *
     * @return void
     */
    public function noThrow($bool)
    {
        $this->_nothrow = $bool;
    }

    /**
     * Returns true if specified slot is filled.
     *
     * @return bool
     */
    public function hasSlot($key)
    {
        if ($this->_parentContext) {
            return $this->_parentContext->hasSlot($key); // setting slots in any context
        }
        return array_key_exists($key, $this->_slots);
    }

    /**
     * Returns the content of specified filled slot.
     *
     * @return string
     */
    public function getSlot($key)
    {
        if ($this->_parentContext) {
            return $this->_parentContext->getSlot($key); // setting slots in any context
        }
        return $this->_slots[$key];
    }

    /**
     * Fill a macro slot.
     *
     * @return void
     */
    public function fillSlot($key, $content)
    {
        if ($this->_parentContext) {
            $this->_parentContext->fillSlot($key, $content); // setting slots in any context
        }
        else $this->_slots[$key] = $content;
    }

    /**
     * Push current filled slots on stack.
     *
     * @return void
     */
    public function pushSlots()
    {
        $this->_slotsStack[] =  $this->_slots;
        $this->_slots = array();
    }

    /**
     * Restore filled slots stack.
     *
     * @return void
     */
    public function popSlots()
    {
        $this->_slots = array_pop($this->_slotsStack);
    }

    /**
     * Context setter.
     *
     * @return void
     */
    public function __set($varname, $value)
    {
        if (preg_match('/^_|\s/', $varname)) {
            throw new PHPTAL_InvalidVariableNameException('Template variable error \''.$varname.'\' must not begin with underscore or contain spaces');
        }
        $this->$varname = $value;
    }

    /**
     * @return bool
     */
    public function __isset($varname)
    {
        // it doesn't need to check isset($this->$varname), because PHP does that _before_ calling __isset()
        return isset($this->_globalContext->$varname) || defined($varname);
    }

    /**
     * Context getter.
     * If variable doesn't exist, it will throw an exception, unless noThrow(true) has been called
     *
     * @return mixed
     */
    public function __get($varname)
    {
        if (property_exists($this, $varname)) { // must use property_exists to avoid calling own __isset().
            return $this->$varname;            // edge case with NULL will be weird
        }

        // must use isset() to allow custom global contexts with __isset()/__get()
        if (isset($this->_globalContext->$varname)) {
            return $this->_globalContext->$varname;
        }

        if (defined($varname)) {
            return constant($varname);
        }

        if ($this->_nothrow) {
            return null;
        }

        throw new PHPTAL_VariableNotFoundException("Unable to find variable '$varname' in current scope", $this->_file, $this->_line);
    }

    private $_slots = array();
    private $_slotsStack = array();
    private $_parentContext = null;
    private $_globalContext = null;
}

/**
 * Resolve TALES path starting from the first path element.
 * The TALES path : object/method1/10/method2
 * will call : phptal_path($ctx->object, 'method1/10/method2')
 *
 * This function is very important for PHPTAL performance.
 *
 * @param mixed  $base    first element of the path ($ctx)
 * @param string $path    rest of the path
 * @param bool   $nothrow is used by phptal_exists(). Prevents this function from
 * throwing an exception when a part of the path cannot be resolved, null is
 * returned instead.
 *
 * @access private
 * @return mixed
 */
function phptal_path($base, $path, $nothrow=false)
{
    if ($base === null) {
        if ($nothrow) return null;
        throw new PHPTAL_VariableNotFoundException("Trying to read property '$path' from NULL");
    }

    foreach (explode('/', $path) as $current) {
        // object handling
        if (is_object($base)) {
            // look for method
            if (method_exists($base, $current)) {
                $base = $base->$current();
                continue;
            }

            // look for variable
            if (property_exists($base, $current)) {
                $base = $base->$current;
                continue;
            }

            if ($base instanceof ArrayAccess && $base->offsetExists($current)) {
                $base = $base->offsetGet($current);
                continue;
            }

            if ($base instanceof Countable && ($current === 'length' || $current === 'size')) {
                $base = count($base);
                continue;
            }

            // look for isset (priority over __get)
            if (method_exists($base, '__isset') && is_callable(array($base, '__isset'))) {
                if ($base->__isset($current)) {
                    $base = $base->$current;
                    continue;
                }
            }
            // ask __get and discard if it returns null
            elseif (method_exists($base, '__get') && is_callable(array($base, '__get'))) {
                $tmp = $base->$current;
                if (null !== $tmp) {
                    $base = $tmp;
                    continue;
                }
            }

            // magic method call
            if (method_exists($base, '__call')) {
                try
                {
                    $base = $base->__call($current, array());
                    continue;
                }
                catch(BadMethodCallException $e){}
            }

            if ($nothrow) {
                return null;
            }

            phptal_path_error($base, $path, $current);
        }

        // array handling
        if (is_array($base)) {
            // key or index
            if (array_key_exists((string)$current, $base)) {
                $base = $base[$current];
                continue;
            }

            // virtual methods provided by phptal
            if ($current == 'length' || $current == 'size') {
                $base = count($base);
                continue;
            }

            if ($nothrow)
                return null;

            phptal_path_error($base, $path, $current);
        }

        // string handling
        if (is_string($base)) {
            // virtual methods provided by phptal
            if ($current == 'length' || $current == 'size') {
                $base = strlen($base);
                continue;
            }

            // access char at index
            if (is_numeric($current)) {
                $base = $base[$current];
                continue;
            }
        }

        // if this point is reached, then the part cannot be resolved

        if ($nothrow)
            return null;

        phptal_path_error($base, $path, $current);
    }

    return $base;
}

/**
 * helper method for phptal_path(). Please don't use it directly.
 *
 * @access private
 */
function phptal_path_error($base, $path, $current)
{
    $basename = '';
    // phptal_path gets data in format ($object, "rest/of/the/path"),
    // so name of the object is not really known and something in its place
    // needs to be figured out
    if ($current !== $path) {
        $pathinfo = " (in path '.../$path')";
        if (preg_match('!([^/]+)/'.preg_quote($current, '!').'(?:/|$)!', $path, $m)) {
            $basename = "'".$m[1]."' ";
        }
    } else $pathinfo = '';

    if (is_array($base)) {
        throw new PHPTAL_VariableNotFoundException("Array {$basename}doesn't have key named '$current'$pathinfo");
    }
    if (is_object($base)) {
        throw new PHPTAL_VariableNotFoundException(ucfirst(get_class($base))." object {$basename}doesn't have method/property named '$current'$pathinfo");
    }
    throw new PHPTAL_VariableNotFoundException(ucfirst(gettype($base))." {$basename}doesn't have property '$current'$pathinfo");
}

/**
 * implements true: modifier
 *
 * @see phptal_path()
 * @param mixed  $ctx  base object
 * @param string $parh rest of the path
 * @access private
 */
function phptal_true($ctx, $path)
{
    $ctx->noThrow(true);
    $res = phptal_path($ctx, $path, true);
    $ctx->noThrow(false);
    return !!$res;
}

/**
 * Returns true if $path can be fully resolved in $ctx context.
 *
 * @access private
 */
function phptal_exists($ctx, $path)
{
    // special note: this method may requires to be extended to a full
    // phptal_path() sibling to avoid calling latest path part if it is a
    // method or a function...
    $ctx->noThrow(true);
    $res = phptal_path($ctx, $path, true);
    $ctx->noThrow(false);
    return $res !== null;
}

/**
 * helper function for conditional expressions
 *
 * @param mixed $var value to check
 * @return bool
 * @access private
 */
function phptal_isempty($var)
{
    return $var === null || $var === false || $var === ''
           || ((is_array($var) || $var instanceof Countable) && count($var)===0);
}

/**
 * convert to string and html-escape given value (of any type)
 *
 * @access private
 */
function phptal_escape($var)
{
    if (is_string($var)) {
        return htmlspecialchars($var, ENT_QUOTES);
    } elseif (is_object($var)) {
        return htmlspecialchars((string)$var, ENT_QUOTES);
    } elseif (is_bool($var)) {
        return (int)$var;
    } elseif (is_array($var)) {
        return htmlspecialchars(implode(', ', $var), ENT_QUOTES);
    }
    return $var;
}

/**
 * convert anything to string
 *
 * @access private
 */
function phptal_tostring($var)
{
    if (is_string($var)) {
        return $var;
    } elseif (is_bool($var)) {
        return (int)$var;
    } elseif (is_array($var)) {
        return implode(', ', $var);
    } elseif ($var instanceof SimpleXMLElement) {
        return $var->asXML();
    }
    return (string)$var;
}
