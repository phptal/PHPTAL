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
require_once 'PHPUnit/Framework/Test.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

error_reporting( E_ALL | E_STRICT );

define('PHPTAL_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR);

require_once dirname(__FILE__)."/config.php";
require_once PHPTAL_DIR.'PHPTAL.php';

class PHPTAL_TestCase extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        //echo $this->getName();
        parent::setUp();
    }
    
    protected $backupGlobals = FALSE;
    
    protected function newPHPTAL($tpl = false)
    {
        $p = new PHPTAL($tpl);
        $p->setForceReparse(true);
        return $p;
    }
}

if (isset($argv) && count($argv) >= 2) {
    array_shift($argv);
    foreach ($argv as $entry) {
        echo "-> running standalone test units $entry\n";
        try
        {
            require_once $entry;
            $class = str_replace('.php', '', $entry);
            $class = basename($class);
            $printer = new PHPUnit_TextUI_ResultPrinter();
            $result = new PHPUnit_Framework_TestResult();
            $result->addListener($printer);
            $testclass = new ReflectionClass($class);
            $suite = new PHPUnit_Framework_TestSuite($testclass);
            $runner = new PHPUnit_TextUI_TestRunner();
            $runner->doRun($suite);
        }
        catch(Exception $e)
        {
            echo "Exception during execution of $entry: ".$e->getMessage()."\n\n";
        }
        
    }
    exit(0);
}

$alltests = new PHPUnit_Framework_TestSuite();
foreach (new DirectoryIterator( dirname(__FILE__) ) as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    
    if (preg_match('/(.*?Test).php$/', $f->getFileName(), $m)) {
        require_once $f->getPathName();
        $alltests->addTestSuite(new PHPUnit_Framework_TestSuite($m[1]));
    }
}

$runner = new PHPUnit_TextUI_TestRunner();
$runner->doRun($alltests);
