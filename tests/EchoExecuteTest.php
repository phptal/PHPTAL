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
 * @version  SVN: $Id: EchoExecuteTest.php 584 2009-04-27 11:45:25Z kornel $
 * @link     http://phptal.org/
 */

require_once dirname(__FILE__)."/config.php";

class EchoExecuteTest extends PHPTAL_TestCase
{    
    private function echoExecute(PHPTAL $tpl)
    {        
        try {
            ob_start();
                $this->assertEquals(0,strlen($tpl->echoExecute()));
            $res = ob_get_clean();
        }
        catch(Exception $e) {
            ob_end_clean();
            throw $e;
        }
        
        $res2 = $tpl->execute();
        $res3 = $tpl->execute();
        
        $this->assertEquals($res2,$res3,"Multiple runs should give same result");
        
        $this->assertEquals($res2,$res,"Execution with and without buffering should give same result");
        
        return trim_string($res);
    }
    
    function testEchoExecute()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<hello/>');
                
        $this->assertEquals("<hello></hello>",$this->echoExecute($tpl));
    }
    
    function testEchoExecuteDecls()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<?xml version="1.0"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><hello/>');
        
        $this->assertEquals(trim_string('<?xml version="1.0"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><hello></hello>'),$this->echoExecute($tpl));
    }
    
    function testEchoExecuteDeclsMacro()
    {
        try
        {
            $tpl = $this->newPHPTAL();
            $tpl->setSource('<?xml version="1.0"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><hello><m metal:define-macro="test">test</m><x metal:use-macro="test"/></hello>');
        
            $this->assertEquals(trim_string('<?xml version="1.0"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><hello><m>test</m></hello>'),$this->echoExecute($tpl));
        }
        catch(PHPTAL_ConfigurationException $e)
        {
            // this is fine. Combination of macros and echoExecute is not supported yet (if it were, the test above is valid)
            $this->assertContains("echoExecute",$e->getMessage());
        }
    }
}
