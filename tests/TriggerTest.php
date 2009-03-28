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
require_once PHPTAL_DIR.'PHPTAL/Trigger.php';

class StupidCacheTrigger implements PHPTAL_Trigger
{
    public $isCaching = false;
    public $cachePath = '';

    public function start($phptalId, $tpl)
    {
        $this->cachePath = 'trigger.' . $tpl->getContext()->someId;

        // if already cached, read the cache and tell PHPTAL to
        // ignore the tag content
        if (file_exists($this->cachePath)){
            $this->isCaching = false;
            readfile($this->cachePath);
            return self::SKIPTAG;
        }
        
        // no cache, we start and output buffer and tell
        // PHPTAL to proceed (ie: execute the tag content)
        $this->isCaching = true;
        ob_start();
        return self::PROCEED;
    }
    
    public function end($phptalId, $tpl)
    {
        // end of tag, if cached file used, do nothing
        if (!$this->isCaching)
            return;

        // otherwise, get the content of the output buffer
        // and write it into the cache file for later usage
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;

        $f = fopen($this->cachePath, 'w');
        fwrite($f, $content);
        fclose($f);
    }
}

class TriggerTest extends PHPTAL_TestCase
{
    public function setUp()
    {
        parent::setUp();
        if (file_exists('trigger.10')) unlink('trigger.10');
        if (file_exists('trigger.11')) unlink('trigger.11');
    }
    
    public function tearDown()
    {
        if (file_exists('trigger.10')) unlink('trigger.10');
        if (file_exists('trigger.11')) unlink('trigger.11');
    }
    
    public function testSimple()
    {
        $trigger = new StupidCacheTrigger();
        $tpl = $this->newPHPTAL('input/trigger.01.html');
        $tpl->addTrigger('someid', $trigger);
        $exp = trim_file('output/trigger.01.html');

        $tpl->someId = 10;
        $res = trim_string($tpl->execute());
        $this->assertEquals($exp, $res);
        $this->assertTrue($trigger->isCaching);
        $this->assertEquals('trigger.10', $trigger->cachePath);

        $tpl->someId = 10;
        $res = trim_string($tpl->execute());
        $this->assertEquals($exp, $res);
        $this->assertFalse($trigger->isCaching);
        $this->assertEquals('trigger.10', $trigger->cachePath);
    }
}

?>
