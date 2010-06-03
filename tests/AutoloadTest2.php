<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */

class AutoloadTest2 extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    function testRegisteredAutoload()
    {
        if (class_exists('PHPTAL',false)) {
            $this->markTestSkipped("Can't test after PHPTAL is included");
        }

        spl_autoload_register(array(__CLASS__,'autoload'));

        self::$autoload_called = false;

        $this->assertFalse(class_exists('TestPHPTALAutoloadNotExists1'),"class must not exist");
        $this->assertTrue(self::$autoload_called, "autoload must be called");

        self::$autoload_called = false;

        set_include_path(
            dirname(__FILE__).'/../classes/' . PATH_SEPARATOR .
            dirname(__FILE__).'/../' . PATH_SEPARATOR .
            get_include_path());
        require_once 'PHPTAL.php';

        $this->assertFalse(class_exists('TestPHPTALAutoloadNotExists2'),"class must not exist");
        $this->assertTrue(self::$autoload_called, "autoload must still be called");
    }

    protected static $autoload_called;

    public static function autoload()
    {
        self::$autoload_called = true;
    }
}
