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

require_once 'PHPTAL/Dom/Node.php';
require_once 'PHPTAL/Dom/Element.php';
require_once 'PHPTAL/Dom/CDATASection.php';

/**
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_Comment extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        if (!preg_match('/^<!--\s*!/', $this->getValueEscaped())) {
            $codewriter->pushHTML($this->getValueEscaped());
        }
    }
}

/**
 * Document text data representation.
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_Text extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->getValueEscaped() !== '') {
            $codewriter->pushHTML($codewriter->interpolateHTML($this->getValueEscaped()));
        }
    }
}

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
        $codewriter->pushHTML($codewriter->interpolateHTML($this->getValueEscaped()));
    }
}

/**
 * Document doctype representation.
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_DocumentType extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setDocType($this->getValueEscaped());
        $codewriter->doDoctype();
    }
}

/**
 * XML declaration node.
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_XmlDeclaration extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setXmlDeclaration($this->getValueEscaped());
        $codewriter->doXmlDeclaration();
    }
}

