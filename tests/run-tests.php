<?php

require_once 'PHPUnit2/Framework/TestResult.php';
require_once 'PHPUnit2/Framework/Test.php';
require_once 'PHPUnit2/Framework/TestCase.php';
require_once 'PHPUnit2/Framework/TestSuite.php';
require_once 'PHPUnit2/TextUI/ResultPrinter.php';

$old_error_report_value = error_reporting( E_ALL | E_STRICT );

$printer = new PHPUnit2_TextUI_ResultPrinter();
$result = new PHPUnit2_Framework_TestResult();
$result->addListener( $printer );

$d = dir( dirname(__FILE__) );
while ($entry = $d->read()) {
    if (preg_match('/(.*?Test).php$/', $entry, $m)) {
        require_once $entry;
        $testclass = new ReflectionClass( $m[1] );
        $suite = new PHPUnit2_Framework_TestSuite($testclass);
        $suite->run($result);
    }
}
$printer->printResult( $result, 0 );


error_reporting( $old_error_report_value );
?>
