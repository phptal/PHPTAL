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


// run-tests.php will include local copy of PHPTAL,
// rather than PEAR's version. This is backup for
// tests ran individually in other environments.
if (!class_exists('PHPTAL'))
{
    if (file_exists(dirname(__FILE__).'/../classes/PHPTAL.php')) {
        require_once dirname(__FILE__).'/../classes/PHPTAL.php';        
    } else {    
        require_once "PHPTAL.php";
    }
}

abstract class PHPTAL_TestCase extends PHPUnit_Framework_TestCase
{
    private $cwd_backup;
    function setUp()
    {        
        // tests rely on cwd being in tests/
        $this->cwd_backup = getcwd();
        chdir(dirname(__FILE__));
        
        parent::setUp();
    }
    
    function tearDown()
    {
        chdir($this->cwd_backup);
    }

    /**
     * backupGlobals is the worst idea ever.
     */
    protected $backupGlobals = FALSE;

    protected function newPHPTAL($tpl = false)
    {
        $p = new PHPTAL($tpl);
        $p->setForceReparse(true);
        return $p;
    }    
}

if (function_exists('date_default_timezone_set'))
{
    date_default_timezone_set(@date_default_timezone_get());
}

function trim_file( $src ){
    return trim_string( join('', file($src) ) );
}

function trim_string( $src ){
    $src = trim($src);
    $src = preg_replace('/\s+/usm', ' ', $src);
    $src = str_replace('\n', ' ', $src);
    $src = preg_replace('/(?<!]])&gt;/', '>', $src); // > may or may not be escaped, except ]]>
    $src = str_replace('> ', '>', $src);
    $src = str_replace(' <', '<', $src);
    $src = str_replace(' />', '/>', $src);
    return $src;
}

// Old versions of PHPUnit seemed to need it
function exception_error_handler($errno, $errstr, $errfile, $errline )
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");


