#!/usr/bin/env php
<?php
/**
 * This is lint tool for checking corectness of template syntax.
 * 
 * You can run it on all your templates after upgrade of PHPTAL to check
 * for potential incompatibilities.
 * 
 * Another good idea is to use it as SVN hook to ensure that you
 * commit only good templates to your repository.
 * 
 * See more:
 * http://phptal.org/wiki/doku.php/lint
 * 
 * or run
 * 
 * ./phptal_lint.php -h
 * 
 */

$lint = new PHPTAL_Lint_CLI();
$lint->main();

class PHPTAL_Lint_CLI
{
    function main()
    {
        try
        {
            if (! empty($_SERVER['REQUEST_URI'])) {
                throw new Exception("Please use this tool from command line");
            }

            $options = $this->extended_getopt(array('-i', '-e'));

            if (isset($options['i'])) {
                $this->include_path($options['i']);
            }

            $this->require_phptal();

            if (isset($options['--filenames--'])) {
                $paths = $options['--filenames--'];
            }

            if (!count($paths)) {
                $this->usage();
                exit(1);
            }

            $lint = new PHPTAL_Lint();

            if (empty($options['i'])) {
                $lint->skipUnknownModifiers(true);
            }

            $custom_extensions = NULL;
            if (isset($options['e'])) {
                $custom_extensions = preg_split('/[\s,.]+/', $options['e'][0]);
                $lint->acceptExtensions($custom_extensions);
                echo "Looking for *.", implode(', *.', $custom_extensions), " files:\n";
            }

            foreach ($paths as $arg) {
                if (is_dir($arg)) {
                    $lint->scan(rtrim($arg, DIRECTORY_SEPARATOR));
                } else {
                    $lint->testFile($arg);
                }
            }

            echo "\n\n";
            echo "Checked ".$this->plural($lint->checked, 'file').".";

            if ($lint->skipped) {
                echo " Skipped ".$this->plural($lint->skipped, "non-template file").".";
            }
            echo "\n";
            if (! $custom_extensions && count($lint->skipped_filenames)) {
                echo "Skipped file(s): ", implode(', ', array_keys($lint->skipped_filenames)), ".\n";
            }

            if (count($lint->errors)) {
                echo "Found ".$this->plural(count($lint->errors), "error").":\n";
                $this->display_erorr_array($lint->errors);
                echo "\n";
                exit(2);
            } else if (count($lint->warnings)) {
                echo "Found ".$this->plural(count($lint->warnings),"warning").":\n";
                $this->display_erorr_array($lint->warnings);
                echo "\n";
                exit(0);
            } else {
                echo "No errors found!\n";
                exit($lint->checked ? 0 : 1);
            }
        }
        catch(Exception $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            $errcode = $e->getCode();
            exit($errcode ? $errcode : 1);
        }
    }


    function display_erorr_array(array $errors)
    {
        $last_dir = '.';
        foreach ($errors as $errinfo) {
            if ($errinfo[0] !== $last_dir) {
                echo "In ", $errinfo[0], ":\n";
                $last_dir = $errinfo[0];
            }
            echo $errinfo[1], ": ", $errinfo[2], ' (line ', $errinfo[3], ')';
            echo "\n";
        }
    }

    function usage()
    {
        $this->require_phptal();
        echo "PHPTAL Lint 1.1.3 (PHPTAL ", strtr(PHPTAL_VERSION,"_","."), ")\n";

        echo "Usage: phptal_lint.php [-e extensions] [-i php_file_or_directory] file_or_directory_to_check ...\n";
        echo "  -e comma-separated list of extensions\n";
        echo "  -i phptales file/include file, or directory\n";
        echo "  Use 'phptal_lint.php .' to scan current directory\n\n";
    }

    function plural($num, $word)
    {
        if ($num == 1) return "$num $word";
        return "$num {$word}s";
    }

    function extended_getopt(array $options)
    {
        $results = array('--filenames--'=>array());
        for ($i = 1; $i < count($_SERVER['argv']); $i ++) {
            if (in_array($_SERVER['argv'][$i], $options)) {
                $results[substr($_SERVER['argv'][$i], 1)][] = $_SERVER['argv'][++ $i];
            } else if ($_SERVER['argv'][$i] == '--') {
                $results['--filenames--'] = array_merge($results['--filenames--'], array_slice($_SERVER['argv'],$i+1));
                break;
            } else if (substr($_SERVER['argv'][$i], 0, 1) == '-') {
                    $this->usage();
                throw new Exception("{$_SERVER['argv'][$i]} is not a valid option\n\n");
            } else {
                $results['--filenames--'][] = $_SERVER['argv'][$i];
            }
        }
        return $results;
    }

    function include_path($tales)
    {
        foreach ($tales as $path) {
            if (is_dir($path)) {
                foreach (new DirectoryIterator($path) as $file) {
                    if (preg_match('/\.php$/', "$path/$file") && is_file("$path/$file")) {
                        include_once ("$path/$file");
                    }
                }
            } else if (preg_match('/\.php$/', $path) && is_file($path)) {
                include_once ("$path");
            }
        }
    }

    function require_phptal()
    {
        if (class_exists('PHPTAL', false)) return;

        $myphptal = dirname(__FILE__) . '/../classes/PHPTAL.php';
        if (file_exists($myphptal)) {
            require_once $myphptal;
        } else {
            require_once "PHPTAL.php";
        }

        if (!class_exists('PHPTAL') || !defined('PHPTAL_VERSION')) {
            throw new Exception("Your PHPTAL installation is broken or too new for this tool");
        }
    }
}

class PHPTAL_Lint
{
    private $ignore_pattern = '/^\.|\.(?i:php|inc|jpe?g|gif|png|mo|po|txt|orig|rej|xsl|xsd|sh|in|ini|conf|css|js|py|pdf|swf|csv|ico|jar|htc)$|^Makefile|^[A-Z]+$/';
    private $accept_pattern = '/\.(?:xml|[px]?html|zpt|phptal|tal|tpl)$/i';
    private $skipUnknownModifiers = false;

    public $errors = array();
    public $warnings = array();
    public $ignored = array();
    public $skipped = 0, $skipped_filenames = array();
    public $checked = 0;

    function skipUnknownModifiers($bool)
    {
        $this->skipUnknownModifiers = $bool;
    }

    function acceptExtensions(array $ext) {
        $this->accept_pattern = '/\.(?:' . implode('|', $ext) . ')$/i';
    }

    protected function reportProgress($symbol)
    {
        echo $symbol;
    }

    function scan($path)
    {
        foreach (new DirectoryIterator($path) as $entry) {
            $filename = $entry->getFilename();

            if ($filename === '.' || $filename === '..') {
                continue;
            }

            if (preg_match($this->ignore_pattern, $filename)) {
                $this->skipped++;
                continue;
            }

            if ($entry->isDir()) {
                $this->reportProgress('.');
                $this->scan($path . DIRECTORY_SEPARATOR . $filename);
                continue;
            }

            if (! preg_match($this->accept_pattern, $filename)) {
                $this->skipped++;
                $this->skipped_filenames[$filename] = true;
                continue;
            }

            $result = $this->testFile($path . DIRECTORY_SEPARATOR . $filename);

            if (self::TEST_OK == $result) {
                $this->reportProgress('.');
            } else if (self::TEST_ERROR == $result) {
                $this->reportProgress('E');
            } else if (self::TEST_SKIPPED == $result) {
                $this->reportProgress('S');
            }
        }
    }

    const TEST_OK = 1;
    const TEST_ERROR = 2;
    const TEST_SKIPPED = 3;

    /**
     * @return int - one of TEST_* constants
     */
    function testFile($fullpath)
    {
        try {
            $this->checked ++;
            $phptal = new PHPTAL($fullpath);
            $phptal->setForceReparse(true);
            $phptal->prepare();
            return self::TEST_OK;
        }
        catch(PHPTAL_UnknownModifierException $e) {
            if ($this->skipUnknownModifiers && is_callable(array($e, 'getModifierName'))) {
                $this->warnings[] = array(dirname($fullpath), basename($fullpath), "Unknown expression modifier: ".$e->getModifierName()." (use -i to include your custom modifier functions)", $e->getLine());
                return self::TEST_SKIPPED;
            }
            $log_exception = $e;
        }
        catch(Exception $e) {
            $log_exception = $e;
        }

        // Takes exception from either of the two catch blocks above
        $this->errors[] = array(dirname($fullpath) , basename($fullpath) , $log_exception->getMessage() , $log_exception->getLine());
        return self::TEST_ERROR;
    }
}

