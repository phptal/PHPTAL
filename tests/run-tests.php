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

require_once 'PHPUnit/Autoload.php';

chdir(dirname(__FILE__));

require_once "./config.php";

if (isset($argv) && count($argv) >= 2) {
    array_shift($argv);
    foreach ($argv as $entry) {
        echo "-> running standalone test units $entry\n";
        try
        {
            $suite = createTestSuiteForFile($entry);
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

    if (preg_match('/(.*?Test).php$/', $f->getFileName())) {
        $alltests->addTestSuite(createTestSuiteForFile($f->getPathName()));
    }
}


$runner = new PHPUnit_TextUI_TestRunner();
$runner->doRun($alltests);

function createTestSuiteForFile($path)
{
    require_once $path;
    $classname = basename($path, '.php');
    if (version_compare(PHP_VERSION, '5.3', '>=') && __NAMESPACE__) {
        $classname = __NAMESPACE__ . '\\' . $classname;
    }
    return new PHPUnit_Framework_TestSuite($classname);
}

