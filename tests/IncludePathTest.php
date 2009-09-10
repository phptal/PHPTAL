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

    function testIncludePathIsChanged()
    {
        $cwd = getcwd();
        chdir(dirname(__FILE__));
        try
        {
            $this->assertFileNotExists("./PHPTAL/Context.php");

            PHPTAL::setIncludePath();
            $modified_include_path = get_include_path();
            try
            {                
                $fp = @fopen("PHPTAL/Context.php","r",true);
                PHPTAL::restoreIncludePath();
            }
            catch(Exception $e)
            {
                PHPTAL::restoreIncludePath();
                
                if (!$e instanceof ErrorException) throw $e;                
                $fp = NULL;
            }            

            $this->assertTrue(!!$fp, "File should be opened via include path: ".$modified_include_path.'; . = '.getcwd());
            
            if ($fp) fclose($fp);

            chdir($cwd);
        }
        catch(Exception $e)
        {
            chdir($cwd); throw $e;
        }
    }
}
