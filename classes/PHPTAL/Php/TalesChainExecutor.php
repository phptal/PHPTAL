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
 * @package PHPTAL.php
 */
interface PHPTAL_Php_TalesChainReader
{
    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor);
    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor);
    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $expression, $islast);
}

/**
 * @package PHPTAL.php
 */
class PHPTAL_Php_TalesChainExecutor
{
    const CHAIN_BREAK = 1;
    const CHAIN_CONT  = 2;

    public function __construct(PHPTAL_Php_CodeWriter $codewriter, $chain, $reader)
    {
        assert(is_array($chain));
        $this->_chain = $chain;
        $this->_chainStarted = false;
        $this->codewriter = $codewriter;
        $this->_reader = $reader;
        $this->_executeChain();
    }

    public function getCodeWriter()
    {
        return $this->codewriter;
    }

    public function doIf($condition)
    {
        if ($this->_chainStarted == false) {
            $this->_chainStarted = true;
            $this->codewriter->doIf($condition);
        } else {
            $this->codewriter->doElseIf($condition);
        }
    }

    public function doElse()
    {
        if ($this->_chainStarted) {
            $this->codewriter->doElse();
        }
    }

    public function breakChain()
    {
        $this->_state = self::CHAIN_BREAK;
    }

    public function continueChain()
    {
        $this->_state = self::CHAIN_CONT;
    }

    private function _executeChain()
    {
        $this->codewriter->noThrow(true);

        end($this->_chain); $lastkey = key($this->_chain);

        foreach ($this->_chain as $key => $exp) {
            $this->_state = 0;
            if ($exp == PHPTAL_TALES_NOTHING_KEYWORD) {
                $this->_reader->talesChainNothingKeyword($this);
                if ($this->_state == self::CHAIN_BREAK)
                    break;
                if ($this->_state == self::CHAIN_CONT)
                    continue;
            } elseif ($exp == PHPTAL_TALES_DEFAULT_KEYWORD) {
                $this->_reader->talesChainDefaultKeyword($this);
                if ($this->_state == self::CHAIN_BREAK)
                    break;
                if ($this->_state == self::CHAIN_CONT)
                    continue;
            } else {
                $this->_reader->talesChainPart($this, $exp, $lastkey === $key);
                if ($this->_state == self::CHAIN_BREAK)
                    break;
                if ($this->_state == self::CHAIN_CONT)
                    continue;
            }
        }
        $this->codewriter->doEnd();
        $this->codewriter->noThrow(false);
    }

    private $_state = 0;
    private $_chain;
    private $_chainStarted = false;
    private $codewriter = null;
}
