<?php

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
