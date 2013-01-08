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
 * Document text data representation.
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_Text extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_State $state)
    {
        $codewriter = new PHPTAL_Php_CodeWriter($state);

        if ($this->getValueEscaped() !== '') {
            $codewriter->doEchoRaw($codewriter->interpolateHTML($this->getValueEscaped()));
        }

        return $codewriter->getRoot();
    }
}
