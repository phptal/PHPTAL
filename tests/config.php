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
 * @link     http://phptal.motion-twin.com/ 
 */
$testDir = dirname(__FILE__);
chdir($testDir);

if (function_exists('date_default_timezone_set'))
{
    date_default_timezone_set(@date_default_timezone_get());
}

function trim_file( $src ){
    return trim_string( join('', file($src) ) );
}

function trim_string( $src ){
    $src = trim($src);
    $src = preg_replace('/\s+/usm', ' ', $src);
    $src = str_replace('\n', ' ', $src);
    $src = str_replace('> ', '>', $src);
    $src = str_replace(' <', '<', $src);
    $src = str_replace(' />', '/>', $src);
    return $src;
}

function exception_error_handler($errno, $errstr, $errfile, $errline ) 
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");


