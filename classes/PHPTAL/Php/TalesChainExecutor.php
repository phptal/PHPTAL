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
 * @package PHPTAL
 * @subpackage Php
 */
class PHPTAL_Php_TalesChainExecutor
{
    const CHAIN_BREAK = 1;
    const CHAIN_CONT  = 2;

    private int $state = 0;
    private bool $chainStarted = false;

    public function __construct(
        private PHPTAL_Php_CodeWriter       $codewriter,
        private array                       $chain,
        private PHPTAL_Php_TalesChainReader $reader
    )
    {
        $this->_executeChain();
    }

    public function getCodeWriter()
    {
        return $this->codewriter;
    }

    public function doIf(string $condition)
    {
        if ($this->chainStarted == false) {
            $this->chainStarted = true;
            $this->codewriter->doIf($condition);
        } else {
            $this->codewriter->doElseIf($condition);
        }
    }

    public function doElse()
    {
        $this->codewriter->doElse();
    }

    public function breakChain()
    {
        $this->state = self::CHAIN_BREAK;
    }

    public function continueChain()
    {
        $this->state = self::CHAIN_CONT;
    }

    private function _executeChain()
    {
        $this->codewriter->noThrow(true);

        end($this->chain); $lastkey = key($this->chain);

        foreach ($this->chain as $key => $exp) {
            $this->state = 0;

            if ($exp == PHPTAL_Php_TalesInternal::NOTHING_KEYWORD) {
                $this->reader->talesChainNothingKeyword($this);
            } elseif ($exp == PHPTAL_Php_TalesInternal::DEFAULT_KEYWORD) {
                $this->reader->talesChainDefaultKeyword($this);
            } else {
                $this->reader->talesChainPart($this, $exp, $lastkey === $key);
            }

            if ($this->state == self::CHAIN_BREAK)
                break;
            if ($this->state == self::CHAIN_CONT)
                continue;
        }

        $this->codewriter->doEnd('if');
        $this->codewriter->noThrow(false);
    }
}
