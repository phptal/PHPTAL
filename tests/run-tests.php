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

require_once 'PHPUnit/Framework/Test.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

error_reporting( E_ALL | E_STRICT );

define('PHPTAL_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR);

require_once "config.php";
require_once PHPTAL_DIR.'PHPTAL.php';

class PHPTAL_TestCase extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        //echo $this->getName();
        parent::setUp();
    }
}

if (isset($argv) && count($argv) >= 2){
    array_shift($argv);
    foreach($argv as $entry){
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
            echo "Exception during execution of $entry";
        }
        
    }
    exit(0);
}

$alltests = new PHPUnit_Framework_TestSuite();
foreach(new DirectoryIterator( dirname(__FILE__) ) as $f) 
{
    if ($f->isDot() || !$f->isFile()) continue;
    
    if (preg_match('/(.*?Test).php$/', $f->getFileName(), $m)) 
    {
        require_once $f->getPathName();
        $alltests->addTestSuite(new PHPUnit_Framework_TestSuite($m[1]));
    }
}

$runner = new PHPUnit_TextUI_TestRunner();
$runner->doRun($alltests);
