#!/usr/bin/env php
<?php

try {   
    $myphptal = dirname(__FILE__).'/../classes/PHPTAL.php';
    if (file_exists($myphptal))
    {
        require_once $myphptal;
    }
    else
    {
        require_once "PHPTAL.php";
    }

    if (!defined('PHPTAL_VERSION')) {
        throw new Exception("Your PHPTAL installation is broken or too new for this tool");
    }
    
    echo "PHPTAL Lint 1.0 (PHPTAL ",PHPTAL_VERSION,")\n";
    
    if (!empty($_SERVER['REQUEST_URI'])) {
        throw new Exception("Please use this tool from command line");
    }
    
    $paths = array();
    
    $custom_extensions = array();
    
    if (isset($_SERVER['argv']) && count($_SERVER['argv']) > 1)
    {
        $arguments = $_SERVER['argv'];
        for($i=1; $i < count($arguments); $i++)
        {
            if ($arguments[$i] === '-e')
            {
                if ($i < count($arguments)-1)
                {
                    $custom_extensions = array_merge($custom_extensions,preg_split('/[\s,.]+/',$arguments[$i+1]));
                    $i++;
                }
            }
            else
            {
                $paths[] = $arguments[$i];
            }            
        }        
    }
    
    if (!count($paths))
    {    
        echo "Usage: phptal_lint.php [-e extensions] file_or_directory_to_check ...\n";
        echo "  -e comma-separated list of extensions\n";
        echo "  Use 'phptal_lint.php .' to scan current directory\n\n";
        exit(1);
    }
    
    $lint = new PHPTAL_Lint();
    
    if ($custom_extensions)
    {
        $lint->acceptExtensions($custom_extensions);
        echo "Using *.",implode(', *.',$custom_extensions),"\n";
    }

    foreach($paths as $arg)
    {
        if (is_dir($arg))
        {
            $lint->scan($arg);
        }
        else
        {
            $lint->testFile($arg);
        }
    }
    
    echo "\n\n";
    echo "Checked {$lint->checked} file(s).";
    if ($lint->skipped) echo " Skipped {$lint->skipped} non-template file(s).";
    echo "\n";
    if (!$custom_extensions && count($lint->skipped_filenames)) {
        echo "Skipped file(s): ",implode(', ',array_keys($lint->skipped_filenames)),".\n";
    }
    
    if (count($lint->errors))
    {
        echo "Found ",count($lint->errors)," error(s):\n";
        $last_dir = NULL;
        foreach($lint->errors as $errinfo)
        {
            if ($errinfo[0] !== $last_dir) {
                echo "In ",$errinfo[0],":\n";
                $last_dir = $errinfo[0];
            }
            echo $errinfo[1],": ",$errinfo[2],' (line ',$errinfo[3],')';
            echo "\n";
        }
        echo "\n";
        exit(2);
    }
    else
    {
        echo "No errors found!\n";
        exit($lint->checked ? 0 : 1);
    }
}
catch(Exception $e)
{
    fwrite(STDOUT,$e->getMessage()."\n");
    $errcode = $e->getCode();
    exit($errcode ? $errcode : 1);
}

class PHPTAL_Lint {
    private $ignore_pattern = '/^\.|\.(?i:php|inc|jpe?g|gif|png|mo|po|txt|orig|rej|xsl|xsd|sh|in|ini|conf|css|js|py|pdf|swf|csv|ico|jar|htc)$|^Makefile|^[A-Z]+$/';
    private $accept_pattern = '/\.(?:xml|x?html|zpt|phptal|tal|tpl)$/i';
    
    public $errors = array();
    public $ignored = array();
    public $skipped = 0;
    public $checked = 0;
    
    function acceptExtensions(array $ext)
    {
        $this->accept_pattern = '/\.(?:'.implode('|',$ext).')$/i';
    }
    
    function scan($path)
    {
        foreach(new DirectoryIterator($path) as $entry)
        {
            $filename = $entry->getFilename();
            
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            
            if (preg_match($this->ignore_pattern,$filename)) {
                $this->skipped++;
                continue;
            }
        
            if ($entry->isDir()) {
                echo '.';
                $this->scan($path . DIRECTORY_SEPARATOR . $filename);
                continue;
            }

            if (!preg_match($this->accept_pattern,$filename))
            {
                $this->skipped++;
                $this->skipped_filenames[$filename] = true;
                continue;
            }            
            
            $this->testFile($path . DIRECTORY_SEPARATOR . $filename);
        }
    }
    
    function testFile($fullpath)
    {
        try
        {            
            $this->checked++;
            $phptal = new PHPTAL($fullpath);
            $phptal->prepare();                
            echo '.';
        }
        catch(PHPTAL_UnknownModifierException $e)
        {
            echo 'S';
        }
        catch(Exception $e)
        {
            echo 'E';
            $this->errors[] = array(dirname($fullpath),basename($fullpath), $e->getMessage(), $e->getLine());
        }
    }
}

