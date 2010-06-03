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

if (!function_exists('__autoload')) {
    function __autoload($class)
    {
        global $autoload_called;
        $autoload_called = true;
    }
}

class AutoloadTest1 extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;

    function testOldAutoload()
    {
        if (class_exists('PHPTAL',false)) {
            $this->markTestSkipped("Can't test after PHPTAL is included");
        }

        global $autoload_called;
        $autoload_called = false;

        $this->assertFalse(class_exists('TestPHPTALAutoloadNotExists1'),"class must not exist");
        $this->assertTrue($autoload_called, "autoload must be called");

        $autoload_called = false;

        set_include_path(
            dirname(__FILE__).'/../classes/' . PATH_SEPARATOR .
            dirname(__FILE__).'/../' . PATH_SEPARATOR .
            get_include_path());
        require_once 'PHPTAL.php';

        $this->assertFalse(class_exists('TestPHPTALAutoloadNotExists2'),"class must not exist");
        $this->assertTrue($autoload_called, "autoload must still be called");

    }
}
