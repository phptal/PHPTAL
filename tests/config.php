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


// This is needed to run tests ran individually without run-tests.php script
if (!class_exists('PHPTAL')) {
    ob_start();
    
    // try local copy of PHPTAL first, otherwise it might be testing
    // PEAR version (or another in include path) causing serious WTF!?s.
    if (file_exists(dirname(__FILE__).'/../classes/PHPTAL.php')) {
        require_once dirname(__FILE__).'/../classes/PHPTAL.php';        
    } elseif (file_exists(dirname(__FILE__).'/../PHPTAL.php')) {
        require_once dirname(__FILE__).'/../PHPTAL.php';        
    } else {
        require_once "PHPTAL.php";
    }
    $out = ob_get_clean();
    if (strlen($out)) {
        throw new Exception("Inclusion of PHPTAL causes output: '$out'");
    }
}

abstract class PHPTAL_TestCase extends PHPUnit_Framework_TestCase
{
    private $cwd_backup, $buffer_level;
    function setUp()
    {        
        $this->buffer_level = ob_get_level();
        
        // tests rely on cwd being in tests/
        $this->cwd_backup = getcwd();
        chdir(dirname(__FILE__));
        
        parent::setUp();
    }
    
    function tearDown()
    {
        parent::tearDown();
        
        chdir($this->cwd_backup);
        
        $unflushed = 0;
        while(ob_get_level() > $this->buffer_level) {
            ob_end_flush(); $unflushed++;
        }
        
        if ($unflushed) throw new Exception("Unflushed buffers: $unflushed");
    }

    /**
     * backupGlobals is the worst idea ever.
     */
    protected $backupGlobals = false;

    protected function newPHPTAL($tpl = false)
    {
        $p = new PHPTAL($tpl);
        $p->setForceReparse(true);
        return $p;
    }    
}

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(@date_default_timezone_get());
}

function normalize_html_file($src) {
    return normalize_html(file_get_contents($src));
}

function normalize_html($src) {
    $src = trim($src);
    $src = preg_replace('/\s+/usm', ' ', $src);
    $src = preg_replace('/(?<!]])&gt;/', '>', $src); // > may or may not be escaped, except ]]>
    $src = str_replace('> ', '>', $src);
    $src = str_replace(' <', '<', $src);
    $src = str_replace(' />', '/>', $src);
    return $src;
}

function normalize_phpsource($code, $ignore_newlines = false) {
    
    $lines = explode("\n", $code);
    $code = "";
    foreach ($lines as $line) {
        $code .= trim($line).($ignore_newlines? '':"\n");
    }
    
    // ignore some no-ops
    return str_replace(array('<?php ?>','<?php ; ?>','{;'),array('','','{'),$code);
}
