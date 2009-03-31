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
// METAL Specification 1.0
//
//      argument ::= Name
//
// Example:
//
//      <table metal:define-macro="sidebar">
//        <tr><th>Links</th></tr>
//        <tr><td metal:define-slot="links">
//          <a href="/">A Link</a>
//        </td></tr>
//      </table>
//
// PHPTAL: (access to slots may be renamed)
//
//  <?php function XXXX_macro_sidebar( $tpl ) { ? >
//      <table>
//        <tr><th>Links</th></tr>
//        <tr>
//        <?php if (isset($tpl->slots->links)): ? >
//          <?php echo $tpl->slots->links ? >
//        <?php else: ? >  
//        <td>
//          <a href="/">A Link</a>
//        </td></tr>
//      </table>
//  <?php } ? >
//

/**
 * @package PHPTAL.php.attribute.metal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_METAL_DefineSlot extends PHPTAL_Php_Attribute
{
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->doIf('$ctx->hasSlot('.$codewriter->str($this->expression).')');
        $codewriter->pushRawHtml('<?php echo $ctx->getSlot('.$codewriter->str($this->expression).') ?>');
        $codewriter->doElse();
    }
    
    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->doEnd();
    }
}

?>
