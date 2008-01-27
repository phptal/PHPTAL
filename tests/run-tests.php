<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

require_once 'PHPUnit/Framework/TestResult.php';
require_once 'PHPUnit/Framework/Test.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/ResultPrinter.php';

if (substr(PHP_OS,0,3) == 'WIN'){
    define('PHPTAL_PHP_CODE_DESTINATION', 'c:\\windows\\temp\\');
}

error_reporting( E_ALL | E_STRICT );

if (isset($argv) && count($argv) >= 2){
    array_shift($argv);
    foreach ($argv as $entry){
        echo "-> running standalone test units $entry\n";
        require_once $entry;
        $class = str_replace('.php', '', $entry);
        $class = basename($class);
        $printer = new PHPUnit_TextUI_ResultPrinter();
        $result = new PHPUnit_Framework_TestResult();
        $result->addListener($printer);
        $testclass = new ReflectionClass($class);
        $suite = new PHPUnit_Framework_TestSuite($testclass);
        $suite->run($result);
        $printer->printResult($result, 0);
    }
    exit(0);
}

$printer = new PHPUnit_TextUI_ResultPrinter();
$result = new PHPUnit_Framework_TestResult();
$result->addListener( $printer );
$d = dir( dirname(__FILE__) );
while ($entry = $d->read()) {
    if (preg_match('/(.*?Test).php$/', $entry, $m)) {
        require_once $entry;
        $testclass = new ReflectionClass( $m[1] );
        $suite = new PHPUnit_Framework_TestSuite($testclass);
        $suite->run($result);
    }
}
$printer->printResult( $result, 0 );

?>
