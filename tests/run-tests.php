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
//require_once 'PHPUnit/Framework/Test.php';
//require_once 'PHPUnit/Framework/TestCase.php';
//require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

error_reporting( E_ALL | E_STRICT );
assert_options(ASSERT_ACTIVE,1);

chdir(dirname(__FILE__));

require_once "./config.php";

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
        $classname = $m[1];
        if (version_compare(PHP_VERSION, '5.3', '>=') && __NAMESPACE__) {
            $classname = __NAMESPACE__ . '\\' . $classname;
        }
        $alltests->addTestSuite(new PHPUnit_Framework_TestSuite($classname));
    }
}


$runner = new PHPUnit_TextUI_TestRunner();
$runner->doRun($alltests);


