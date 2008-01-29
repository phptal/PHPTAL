<?php

/**
 * @package phptal.php
 */
interface PHPTAL_Php_TalesChainReader
{
    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor);
    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor);
    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $expression, $islast);
}

/**
 * @package phptal.php
 */
class PHPTAL_Php_TalesChainExecutor
{
    const CHAIN_BREAK = 1;
    const CHAIN_CONT  = 2;

    public function __construct($generator, $chain, $reader)
    {
        assert(is_array($chain));
        $this->_chain = $chain;
        $this->_chainStarted = false;
        $this->_chainGenerator = $generator;
        $this->_reader = $reader;
        $this->_executeChain();
    }
    
    public function doIf($condition)
    {
        if ($this->_chainStarted == false){
            $this->_chainStarted = true;
            $this->_chainGenerator->doIf($condition);
        }
        else {
            $this->_chainGenerator->doElseIf($condition);
        }
    }

    public function doElse()
    {
        if ($this->_chainStarted){
            $this->_chainGenerator->doElse();
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
        $this->_chainGenerator->noThrow(true);
        
        end($this->_chain); $lastkey = key($this->_chain);
        
        foreach ($this->_chain as $key => $exp){
            $this->_state = 0;
            if ($exp == PHPTAL_TALES_NOTHING_KEYWORD){
                $this->_reader->talesChainNothingKeyword($this);
                if ($this->_state == self::CHAIN_BREAK)
                    break;
                if ($this->_state == self::CHAIN_CONT)
                    continue;
            }
            else if ($exp == PHPTAL_TALES_DEFAULT_KEYWORD){
                $this->_reader->talesChainDefaultKeyword($this);
                if ($this->_state == self::CHAIN_BREAK)
                    break;
                if ($this->_state == self::CHAIN_CONT)
                    continue;
            }
            else {
                $this->_reader->talesChainPart($this, $exp, $lastkey === $key);
                if ($this->_state == self::CHAIN_BREAK)
                    break;
                if ($this->_state == self::CHAIN_CONT)
                    continue;
            }
        }
        $this->_chainGenerator->doEnd();
        $this->_chainGenerator->noThrow(false);
    }
    
    private $_state = 0;
    private $_chain;
    private $_chainStarted = false;
    private $_chainGenerator = null;
}

?>
