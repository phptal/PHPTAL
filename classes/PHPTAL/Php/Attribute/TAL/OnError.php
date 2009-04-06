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

/**
 * TAL Specifications 1.4
 * 
 *      argument ::= (['text'] | 'structure') expression
 * 
 * Example:
 * 
 *      <p tal:on-error="string: Error! This paragraph is buggy!">
 *      My name is <span tal:replace="here/SlimShady" />.<br />
 *      (My login name is 
 *      <b tal:on-error="string: Username is not defined!" 
 *         tal:content="user">Unknown</b>)
 *      </p>
 * 
 * @package PHPTAL.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_OnError extends PHPTAL_Php_Attribute
{
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->doTry();
        $codewriter->pushCode('ob_start()');
    }
    
    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
        $var = $codewriter->createTempVariable();
        
        $codewriter->pushCode('ob_end_flush()');        
        $codewriter->doCatch('Exception '.$var);
        $codewriter->pushCode('$tpl->addError('.$var.')');
        $codewriter->pushCode('ob_end_clean()');

        $expression = $this->extractEchoType($this->expression);

        $code = $codewriter->evaluateExpression($expression);
        switch ($code) {
            case PHPTAL_TALES_NOTHING_KEYWORD:
                break;

            case PHPTAL_TALES_DEFAULT_KEYWORD:
                $codewriter->pushRawHtml('<pre class="phptalError"');
                $codewriter->doEchoRaw($var);
                $codewriter->pushRawHtml('</pre>');
                break;
                
            default:
                $this->doEchoAttribute($codewriter, $code);
                break;
        }
        $codewriter->doEnd();
        
        $codewriter->recycleTempVariable($var);
    }
}

