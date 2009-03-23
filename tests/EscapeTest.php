<?php

require_once 'config.php';

class EscapeTest extends PHPTAL_TestCase {

    private function executeString($str, $params = array())
    {
        $tpl = new PHPTAL();
        foreach($params as $k => $v) $tpl->set($k,$v);
        $tpl->setSource($str);
        return $tpl->execute();
    }

	function testDoesEscapeHTMLContent(){
		$tpl = new PHPTAL('input/escape.html');
		$exp = trim_file('output/escape.html');
		$res = trim_string($tpl->execute());
		$this->assertEquals($exp, $res);
	}
    
    function testEntityDecodingPath1()
    {
        $res = $this->executeString('<div title="&quot;" class=\'&quot;\' tal:content="\'&quot; quote character\'" />');
        $this->assertNotContains('&amp;',$res);
    }
    
    
    function testDecodingBeforeStructure()
    {
        $res = $this->executeString('<div tal:content="structure php:\'&amp; quote character\'" />');
        $this->assertNotContains('&amp;',$res);
    }
    
    function testEntityDecodingPHP1()
    {
        $res = $this->executeString('<div tal:content="php:\'&quot; quote character\'" />');
        $this->assertNotContains('&amp;',$res);
    }

    function testEntityDecodingPath2()
    {
        $res = $this->executeString('<div tal:attributes="title \'&quot; quote character\'" />');
        $this->assertNotContains('&amp;',$res);
    }
    
    function testEntityDecodingPHP2()
    {
        $res = $this->executeString('<div tal:attributes="title php:\'&quot; quote character\'" />');
        $this->assertNotContains('&amp;',$res);
    }
    
    function testEntityDecodingPath3()
    {
        $res = $this->executeString('<p>${\'&quot; quote character\'}</p>');
        $this->assertNotContains('&amp;',$res);
    }
    
    function testEntityDecodingPHP3()
    {
        $res = $this->executeString('<p>${php:\'&quot; quote character\'}</p>');
        $this->assertNotContains('&amp;',$res);
    }
    
    
    function testEntityEncodingPath1()
    {
        $res = $this->executeString('<div tal:content="\'&amp; ampersand character\'" />');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }
    
    function testEntityEncodingPHP1()
    {
        $res = $this->executeString('<div tal:content="php:\'&amp; ampersand character\'" />');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }
    
    function testEntityEncodingPath2()
    {
        $res = $this->executeString('<div tal:attributes="title \'&amp; ampersand character\'" />');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }

    function testEntityEncodingVariables()
    {
        $res = $this->executeString('<div tal:attributes="title variable; class variable">${variable}${php:variable}</div>', 
                                    array('variable'=>'& = ampersand, " = quote, \' = apostrophe'));
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }

    function testEntityEncodingAttributesDefault1()
    {
        $res = $this->executeString('<div tal:attributes="title idontexist | default" title=\'&amp; ampersand character\' />');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }

    function testEntityEncodingAttributesDefault2()
    {
        $res = $this->executeString('<div tal:attributes="title idontexist | default" title=\'&quot;&apos;\' />');
        $this->assertNotContains('&amp;',$res);
        $this->assertContains('&quot;',$res); // or apos...
    }
    
    function testEntityEncodingPHP2()
    {
        $res = $this->executeString('<div tal:attributes="title php:\'&amp; ampersand character\'" />');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }

    function testEntityEncodingPath3()
    {
        $res = $this->executeString('<p>${\'&amp; ampersand character\'}</p>');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }
    
    function testEntityEncodingPHP3()
    {
        $res = $this->executeString('<p>&{php:\'&amp; ampersand character\'}</p>');
        $this->assertContains('&amp;',$res);
        $this->assertNotContains('&amp;amp;',$res);
        $this->assertNotContains('&amp;&amp;',$res);
    }
}
