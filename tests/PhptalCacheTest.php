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

require_once 'config.php';

$PhptalCacheTest_random = time().mt_rand();

class PhptalCacheTest extends PHPUnit_Framework_TestCase 
{ 
    private function PHPTALWithSource($source)
    {
        global $PhptalCacheTest_random;
        
        $tpl = new PHPTAL();
        $tpl->setForceReparse(false);
        $tpl->setSource($source."<!-- $PhptalCacheTest_random -->"); // avoid cached templates from previous test runs
        return $tpl;
    }
    
    function testBasicCache()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="1h" tal:content="var" />');
        $tpl->var = 'SUCCESS';
        $this->assertContains( "SUCCESS", $tpl->execute() );        
        
        $tpl->var = 'FAIL';
        $res = $tpl->execute();
        $this->assertNotContains( "FAIL", $res );        
        $this->assertContains( "SUCCESS", $res );        
    }
    
    /**
     * tal:define is also cached
     */
    function testDefine()
    {
        $tpl = $this->PHPTALWithSource('<div tal:define="display var" phptal:cache="1h">${display}</div>');
        $tpl->var = 'SUCCESS';
        $this->assertContains( "SUCCESS", $tpl->execute() );        
        
        $tpl->var = 'FAIL';
        $res = $tpl->execute();
        $this->assertNotContains( "FAIL", $res );        
        $this->assertContains( "SUCCESS", $res );        
    }
        
    function testTimedExpiry()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="1s" tal:content="var" />');
        $tpl->var = 'FIRST';
        $this->assertContains( "FIRST", $tpl->execute() );        
        
        sleep(2); // wait for it to expire :)
        
        $tpl->var = 'SECOND';
        $res = $tpl->execute();
        $this->assertContains( "SECOND", $res );        
        $this->assertNotContains( "FIRST", $res );        
    }
    
    function testCacheInStringSource()
    {
        $source = '<div phptal:cache="1d" tal:content="var" />';   
        $tpl = $this->PHPTALWithSource($source);
        $tpl->var = 'FIRST';
        $this->assertContains( "FIRST", $tpl->execute() );
        
        $tpl = $this->PHPTALWithSource($source);
        $tpl->var = 'SECOND';
        $this->assertContains( "FIRST", $tpl->execute() );
    }
    
    function testCleanUpCache()
    {
        $source = '<div phptal:cache="1d" tal:content="var" />';
        
        $tpl = $this->PHPTALWithSource($source);
        $tpl->cleanUpCache();
        
        $tpl->var = 'FIRST';
        $this->assertContains( "FIRST", $tpl->execute() );        
        
        $tpl = $this->PHPTALWithSource($source);
        $tpl->var = 'SECOND';
        $res = $tpl->execute();
        $this->assertContains( "FIRST", $res );        
        $this->assertNotContains( "SECOND", $res );        
        
        $tpl->cleanUpCache();
        
        $tpl->var = 'THIRD';
        $res = $tpl->execute();
        $this->assertContains( "THIRD", $res );        
        $this->assertNotContains( "SECOND", $res );        
        $this->assertNotContains( "FIRST", $res );        
    }
    
    function testPerExpiry()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="1d per var" tal:content="var" />');
        $tpl->var = 'FIRST';
        $this->assertContains( "FIRST", $tpl->execute() );        
        $tpl->var = 'SECOND';
        $res = $tpl->execute();
        $this->assertContains( "SECOND", $res );        
        $this->assertNotContains( "FIRST", $res );        
    }
    
    function testVersions()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="40s per version" tal:content="var" />');
        
        $tpl->var = 'FIRST';
        $tpl->version = '1';
        $this->assertContains( "FIRST", $tpl->execute() );        
        
        $tpl->var = 'FAIL';
        $tpl->version = '1';
        $res = $tpl->execute();
        $this->assertContains( "FIRST", $res );        
        $this->assertNotContains( "FAIL", $res );        
                
        $tpl->var = 'THRID';
        $tpl->version = '3';
        $res = $tpl->execute();
        $this->assertContains( "THRID", $res );        
        $this->assertNotContains( "SECOND", $res );        
        
        $tpl->var = 'FAIL';
        $tpl->version = '3';
        $res = $tpl->execute();
        $this->assertContains( "THRID", $res );        
        $this->assertNotContains( "FAIL", $res );        
    }
    
    function testVariableExpiry()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="vartime s" tal:content="var" />');
        $tpl->vartime = 0;
        $tpl->var = 'FIRST';        
        $this->assertContains( "FIRST", $tpl->execute() );
        
        $tpl->var = 'SECOND'; // time is 0 = no cache 
        $this->assertContains( "SECOND", $tpl->execute() );
        
        $tpl->vartime = 60;   // get it to cache it
        $tpl->var = 'SECOND';
        $this->assertContains( "SECOND", $tpl->execute() );

        $tpl->var = 'THRID';
        $res = $tpl->execute();
        $this->assertContains( "SECOND", $res );
        $this->assertNotContains( "THRID", $res ); // should be cached        
    }
}
