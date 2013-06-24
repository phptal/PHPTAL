<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */


class TalesRegistryTest extends PHPTAL_TestCase
{
    function testInstance()
    {
        $this->assertSame(PHPTAL_TalesRegistry::getInstance(), PHPTAL_TalesRegistry::getInstance());
        $this->assertInstanceOf('PHPTAL_TalesRegistry',PHPTAL_TalesRegistry::getInstance());
    }

    function testRegisterFunction()
    {
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test', 'registry_test_callback');

        $this->assertEquals('<p>ok1</p>', $this->newPHPTAL()->setSource('<p tal:content="registry_test:number:1"/>')->execute());
    }

    /**
     * Define constants after requires/includes
     * @param Text_Template $template
     * @return void
     */
    public function prepareTemplate( Text_Template $template ) {
        $template->setVar( array(
            'constants'    => '',
            'zz_constants' => PHPUnit_Util_GlobalState::getConstantsAsString()
        ));
        parent::prepareTemplate( $template );
    }

    /**
     * @runInSeparateProcess
     * @expectedException PHPTAL_UnknownModifierException
     */
    function testUnregisterFunction()
    {
        $test_prefix = 'testprefix';
        PHPTAL_TalesRegistry::getInstance()->registerPrefix($test_prefix, 'registry_test_callback3');
        PHPTAL_TalesRegistry::getInstance()->unregisterPrefix($test_prefix);
        $this->newPHPTAL()->setSource('<p tal:content="'.$test_prefix.':"/>')->execute();
    }

    /**
     * @expectedException PHPTAL_ConfigurationException
     */
    function testCantUnregisterNonRegistered()
    {
        PHPTAL_TalesRegistry::getInstance()->unregisterPrefix('doesnotexist');
    }

    /**
     * @expectedException PHPTAL_ConfigurationException
     */
    function testCantRegisterNonExistant()
    {
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_2', 'doesnotexist');
    }

    /**
     * @expectedException PHPTAL_ConfigurationException
     */
    function testCantRegisterTwice()
    {
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_3', 'registry_test_callback');
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_3', 'registry_test_callback');
    }

    function testCanRegisterFallbackTwice()
    {
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_4', 'registry_test_callback', true);
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_4', 'registry_test_callback', true);
    }

    function testCanRegisterOverFallback()
    {
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_5', 'registry_test_callback', true);
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_5', 'registry_test_callback2');
    }

    function testCanRegisterFallbackOverRegistered()
    {
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_6', 'registry_test_callback2');
        PHPTAL_TalesRegistry::getInstance()->registerPrefix('registry_test_6', 'registry_test_callback', true);
    }
}

function registry_test_callback($arg, $nothrow)
{
    return '"ok" . ' . phptal_tales($arg);
}

function registry_test_callback2($arg, $nothrow)
{
    return '"ok2" . ' . phptal_tales($arg);
}

function registry_test_callback3($arg, $nothrow)
{
    return '"ok3" . ' . phptal_tales($arg);
}
