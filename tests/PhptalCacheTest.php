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

class PhptalCacheTest extends PHPTAL_TestCase
{
    private string $PhptalCacheTest_random;

    function setUp(): void
    {
        parent::setUp();
        $this->PhptalCacheTest_random =  time().mt_rand();
    }

    private function PHPTALWithSource($source)
    {
        global $PhptalCacheTest_random;

        $tpl = new PHPTAL();
        $tpl->setForceReparse(false);
        $tpl->setSource($source."<!-- {$this->PhptalCacheTest_random} -->"); // avoid cached templates from previous test runs
        return $tpl;
    }

    function testBasicCache()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="1h" tal:content="var" />');
        $tpl->var = 'SUCCESS';
        $this->assertStringContainsString( "SUCCESS", $tpl->execute() );

        $tpl->var = 'FAIL';
        $res = $tpl->execute();
        $this->assertStringNotContainsString( "FAIL", $res );
        $this->assertStringContainsString( "SUCCESS", $res );
    }

    /**
     * tal:define is also cached
     */
    function testDefine()
    {
        $tpl = $this->PHPTALWithSource('<div tal:define="display var" phptal:cache="1h">${display}</div>');
        $tpl->var = 'SUCCESS';
        $this->assertStringContainsString( "SUCCESS", $tpl->execute() );

        $tpl->var = 'FAIL';
        $res = $tpl->execute();
        $this->assertStringNotContainsString( "FAIL", $res );
        $this->assertStringContainsString( "SUCCESS", $res );
    }

    function testTimedExpiry()
    {
        $this->markTestSkipped("slow tests are no fun");

        $tpl = $this->PHPTALWithSource('<div phptal:cache="1s" tal:content="var" />');
        $tpl->var = 'FIRST';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );

        sleep(2); // wait for it to expire :)

        $tpl->var = 'SECOND';
        $res = $tpl->execute();
        $this->assertStringContainsString( "SECOND", $res );
        $this->assertStringNotContainsString( "FIRST", $res );
    }

    function testCacheInStringSource()
    {
        $source = '<div phptal:cache="1d" tal:content="var" />';
        $tpl = $this->PHPTALWithSource($source);
        $tpl->var = 'FIRST';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );

        $tpl = $this->PHPTALWithSource($source);
        $tpl->var = 'SECOND';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );
    }

    function testCleanUpCache()
    {
        $source = '<div phptal:cache="1d" tal:content="var" />';

        $tpl = $this->PHPTALWithSource($source);
        $tpl->cleanUpCache();

        $tpl->var = 'FIRST';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );

        $tpl = $this->PHPTALWithSource($source);
        $tpl->var = 'SECOND';
        $res = $tpl->execute();
        $this->assertStringContainsString( "FIRST", $res );
        $this->assertStringNotContainsString( "SECOND", $res );

        $tpl->cleanUpCache();

        $tpl->var = 'THIRD';
        $res = $tpl->execute();
        $this->assertStringContainsString( "THIRD", $res );
        $this->assertStringNotContainsString( "SECOND", $res );
        $this->assertStringNotContainsString( "FIRST", $res );
    }

    function testPerExpiry()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="1d per var" tal:content="var" />');
        $tpl->var = 'FIRST';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );
        $tpl->var = 'SECOND';
        $res = $tpl->execute();
        $this->assertStringContainsString( "SECOND", $res );
        $this->assertStringNotContainsString( "FIRST", $res );
    }

    function testVersions()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="40s per version" tal:content="var" />');

        $tpl->var = 'FIRST';
        $tpl->version = '1';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );

        $tpl->var = 'FAIL';
        $tpl->version = '1';
        $res = $tpl->execute();
        $this->assertStringContainsString( "FIRST", $res );
        $this->assertStringNotContainsString( "FAIL", $res );

        $tpl->var = 'THRID';
        $tpl->version = '3';
        $res = $tpl->execute();
        $this->assertStringContainsString( "THRID", $res );
        $this->assertStringNotContainsString( "SECOND", $res );

        $tpl->var = 'FAIL';
        $tpl->version = '3';
        $res = $tpl->execute();
        $this->assertStringContainsString( "THRID", $res );
        $this->assertStringNotContainsString( "FAIL", $res );
    }

    function testVariableExpiry()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="vartime s" tal:content="var" />');
        $tpl->vartime = 0;
        $tpl->var = 'FIRST';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );

        $tpl->var = 'SECOND'; // time is 0 = no cache
        $this->assertStringContainsString( "SECOND", $tpl->execute() );

        $tpl->vartime = 60;   // get it to cache it
        $tpl->var = 'SECOND';
        $this->assertStringContainsString( "SECOND", $tpl->execute() );

        $tpl->var = 'THRID';
        $res = $tpl->execute();
        $this->assertStringContainsString( "SECOND", $res );
        $this->assertStringNotContainsString( "THRID", $res ); // should be cached
    }

    function testVariableExpressionExpiry()
    {
        $tpl = $this->PHPTALWithSource('<div phptal:cache="tales/vartime s" tal:content="var" />');
        $tpl->tales = array('vartime' => 0);
        $tpl->var = 'FIRST';
        $this->assertStringContainsString( "FIRST", $tpl->execute() );

        $tpl->var = 'SECOND'; // time is 0 = no cache
        $this->assertStringContainsString( "SECOND", $tpl->execute() );

        $tpl->tales = array('vartime' => 60);   // get it to cache it
        $tpl->var = 'SECOND';
        $this->assertStringContainsString( "SECOND", $tpl->execute() );

        $tpl->var = 'THRID';
        $res = $tpl->execute();
        $this->assertStringContainsString( "SECOND", $res );
        $this->assertStringNotContainsString( "THRID", $res ); // should be cached
    }
}
