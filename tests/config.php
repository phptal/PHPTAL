<?php
error_reporting(E_ALL | E_STRICT);
$testDir = dirname(__FILE__);
chdir($testDir);

define('PHPTAL_FORCE_REPARSE', 1);
ini_set('include_path', $testDir .'/../classes:'. ini_get('include_path'));


function trim_file( $src ){
    return trim_string( join('', file($src) ) );
}

function trim_string( $src ){
    $src = trim($src);
    $src = preg_replace('/\s+/sm', ' ', $src);
    $src = str_replace('\n', ' ', $src);
    $src = str_replace('> ', '>', $src);
    $src = str_replace(' <', '<', $src);
    return $src;
}


?>
