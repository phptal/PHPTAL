<?php

define('_PHPTAL_VERSION',      '1.0.3');
define('_PHPTAL_MAILING_LIST', 'http://lists.motion-twin.com/mailman/listinfo/phptal');
define('_PHPTAL_SUBVERSION',   'https://svn.motion-twin.com/phptal');
define('_PHPTAL_RSSHREF',      '/rss20.xml');


define('IN', 'src/');
define('OUT', 'www/');
define('TPL', 'tpl/');

define('PHPTAL_FORCE_REPARSE',1);

require_once 'PHPTAL.php';
require_once 'PHPTAL/Filter.php';

class CodePreFilter implements PHPTAL_Filter
{
    function filter($txt)
    {
        while (preg_match('/\s*?<!--code\s+(.*?)\s+-->\s+/is', $txt, $m)){
            list($src,$data) = $m;
            $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
            $txt = str_replace($src,$data,$txt);
        }
        return $txt;
    }
}


$d = dir(IN);
while ($entry = $d->read()){
    $realPath = IN . $entry;
    if (is_file($realPath) && $entry[0] != '.'){
        echo '* parsing ', $entry, "\n";
        $t = new PHPTAL($realPath);
        $t->setTemplateRepository(TPL);
        $t->setPreFilter(new CodePreFilter());
        $t->VERSION = _PHPTAL_VERSION;
        $t->MAILING = _PHPTAL_MAILING_LIST;
        $t->SUBVERS = _PHPTAL_SUBVERSION;
        $t->RSSHREF = _PHPTAL_RSSHREF;
        try {
            $r = $t->execute();
        }
        catch (Exception $e){
            echo $e;
            die('');
        }

        $out = OUT . $entry;
        $fp = fopen($out, 'w');
        if (!$fp){
            die('Unable to open '.$out.' for writing'."\n");
        }
        fwrite($fp, $r);
        fclose($fp);
    }
}

?>
