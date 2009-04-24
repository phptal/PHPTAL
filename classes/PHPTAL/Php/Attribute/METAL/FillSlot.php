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
 *  METAL Specification 1.0
 *
 *      argument ::= Name
 *
 * Example:
 *
 *       <table metal:use-macro="here/doc1/macros/sidebar">
 *        <tr><th>Links</th></tr>
 *        <tr><td metal:fill-slot="links">
 *          <a href="http://www.goodplace.com">Good Place</a><br>
 *          <a href="http://www.badplace.com">Bad Place</a><br>
 *          <a href="http://www.otherplace.com">Other Place</a>
 *        </td></tr>
 *      </table>
 *
 * PHPTAL:
 *
 * 1. evaluate slots
 *
 * <?php ob_start(); ? >
 * <td>
 *   <a href="http://www.goodplace.com">Good Place</a><br>
 *   <a href="http://www.badplace.com">Bad Place</a><br>
 *   <a href="http://www.otherplace.com">Other Place</a>
 * </td>
 * <?php $tpl->slots->links = ob_get_contents(); ob_end_clean(); ? >
 *
 * 2. call the macro (here not supported)
 *
 * <?php echo phptal_macro($tpl, 'master_page.html/macros/sidebar'); ? >
 *
 *
 * @package PHPTAL.php.attribute.metal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_METAL_FillSlot extends PHPTAL_Php_Attribute
{
    public function before(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushCode('ob_start()');
    }

    public function after(PHPTAL_Php_CodeWriter $codewriter)
    {
        $code = '$ctx->fillSlot("'.$this->expression.'", ob_get_clean())';
        $codewriter->pushCode($code);
    }
}


