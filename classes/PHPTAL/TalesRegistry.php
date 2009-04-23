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

/**
 * You can implement this interface to create custom tales modifiers
 *
 * Methods suitable for modifiers must be static.
 *
 * @package PHPTAL.php
 */
interface PHPTAL_Tales
{
}

/**
 * Global registry of TALES expression modifiers
 *
 * @package PHPTAL.php
 */
class PHPTAL_TalesRegistry
{

    static $instance;

    static public function initialize()
    {
        self::$instance = new PHPTAL_TalesRegistry();
    }

    /**
     * This is a singleton
     *
     * @return PHPTAL_TalesRegistry
     */
    static public function getInstance()
    {
        if (!(self::$instance instanceof PHPTAL_TalesRegistry)) {
            self::initialize();
        }

        return self::$instance;
    }

    protected function __construct()
    {
    }

    /**
     *
     * Expects an either a function name or an array of class and method as
     * callback.
     *
     * @param string $prefix
     * @param mixed $callback
     */
    public function registerPrefix($prefix, $callback)
    {
        if ($this->isRegistered($prefix)) {
            throw new PHPTAL_ConfigurationException("Expression modifier '$prefix' is already registered");
        }

        // Check if valid callback

        if (is_array($callback)) {

            $class = new ReflectionClass($callback[0]);

            if (!$class->isSubclassOf('PHPTAL_Tales')) {
                throw new PHPTAL_ConfigurationException('The class you want to register does not implement "PHPTAL_Tales".');
            }

            $method = new ReflectionMethod($callback[0], $callback[1]);

            if (!$method->isStatic()) {
                throw new PHPTAL_ConfigurationException('The method you want to register is not static.');
            }

            // maybe we want to check the parameters the method takes

        } else {
            if (!function_exists($callback)) {
                throw new PHPTAL_ConfigurationException('The function you are trying to register does not exist.');
            }
        }


        $this->_callbacks[$prefix] = $callback;
    }

    /**
     * true if given prefix is taken
     */
    public function isRegistered($prefix)
    {
        return (array_key_exists($prefix, $this->_callbacks));
    }

    /**
     * get callback for the prefix
     */
    public function getCallback($prefix)
    {
        if (!$this->isRegistered($prefix)) {
            throw new PHPTAL_ConfigurationException("Expression modifier '$prefix' is not registered");
        }
        return $this->_callbacks[$prefix];
    }

    private $_callbacks = array();
}

