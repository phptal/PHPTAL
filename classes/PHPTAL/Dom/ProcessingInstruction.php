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

/**
 * processing instructions, including <?php blocks
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_ProcessingInstruction extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $types = ini_get('short_open_tag')?'php|=|':'php';
        if (preg_match("/<\?($types)(.*?)\?>/", $this->getValueEscaped(), $m)) {
            list(,$type,$code) = $m;
            if ($type === '=') $code = 'echo '.$code;
            // block will be executed as PHP
            $codewriter->pushCode(new PHPTAL_Expr_PHP($code));
        } else {
            $codewriter->doEchoRaw(new PHPTAL_Expr_String("<"));
            $codewriter->doEchoRaw($codewriter->interpolateHTML(substr($this->getValueEscaped(), 1)));
        }
    }
}
