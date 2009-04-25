<?php

require_once dirname(__FILE__)."/config.php";

class IncludePathTest extends PHPTAL_TestCase
{
    private $cwd;

    function testOverride()
    {
        $path = get_include_path();

        PHPTAL::setIncludePath();
        $this->assertNotEquals($path,get_include_path());
        PHPTAL::restoreIncludePath();
        $this->assertEquals($path,get_include_path());
    }

    function testPreserveCustomPath()
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . "./my-custom-path-test/");

        $this->assertContains("./my-custom-path-test/", $path = get_include_path());

        PHPTAL::setIncludePath();
        $this->assertNotEquals($path,get_include_path());
        PHPTAL::restoreIncludePath();
        $this->assertEquals($path,get_include_path());

        $this->assertContains("./my-custom-path-test/", get_include_path());
    }

    function testNesting()
    {
        $path = get_include_path();

        PHPTAL::setIncludePath();
        $this->assertNotEquals($path,get_include_path());

        PHPTAL::setIncludePath();
        $this->assertNotEquals($path,get_include_path());

        PHPTAL::setIncludePath();
        $this->assertNotEquals($path,get_include_path());


        PHPTAL::restoreIncludePath();
        $this->assertNotEquals($path,get_include_path());

        PHPTAL::restoreIncludePath();
        $this->assertNotEquals($path,get_include_path());

        PHPTAL::restoreIncludePath();
        $this->assertEquals($path,get_include_path());
    }

    function testPath()
    {
        try
        {
            $cwd = getcwd();
            chdir(dirname(__FILE__));

            $this->assertFileNotExists("./PHPTAL/Context.php");

            $fp = fopen("PHPTAL/Context.php","r",true);

            $this->assertTrue(!!$fp, "File opened via include path");

            fclose($fp);

            chdir($cwd);
        }
        catch(Exception $e)
        {
            chdir($cwd); throw $e;
        }
    }
}
