<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */



class TokenizerTest extends PHPTAL_TestCase
{
    function testParse()
    {
        $t = new PHPTAL_Tokenizer('123', array('DIGIT'=>'\d'));

        $this->assertEquals('DIGIT', $t->nextToken());
        $this->assertEquals('1', $t->tokenValue());
        $this->assertEquals('DIGIT', $t->nextToken());
        $this->assertEquals('2', $t->tokenValue());
        $this->assertEquals('DIGIT', $t->nextToken());
        $this->assertEquals('3', $t->tokenValue());
    }

    function testParse2()
    {
        $t = new PHPTAL_Tokenizer('2+3', array('DIGIT'=>'\d+', 'PLUS'=>'\+'));

        $this->assertEquals('DIGIT', $t->nextToken());
        $this->assertEquals('2', $t->tokenValue());
        $this->assertEquals('DIGIT', $t->token());
        $this->assertEquals('2', $t->tokenValue());
        $this->assertEquals('2', $t->tokenValue());

        $this->assertEquals('PLUS', $t->nextToken());
        $this->assertEquals('PLUS', $t->token());
        $this->assertEquals('+', $t->tokenValue());
        $this->assertEquals('+', $t->tokenValue());

        $this->assertEquals('DIGIT', $t->nextToken());
        $this->assertEquals('3', $t->tokenValue());

        $this->assertEquals('EOF', $t->nextToken());
        $this->assertEquals('EOF', $t->nextToken());
        $this->assertEquals('EOF', $t->nextToken());
        $this->assertEquals('EOF', $t->nextToken());
    }

    function testSkipSpace()
    {
        $t = new PHPTAL_Tokenizer('2   + 3', array('DIGIT'=>'\d+', 'PLUS'=>'\+', 'SPACE'=>' '));

        $this->assertEquals('DIGIT', $t->nextToken());
        $this->assertEquals('2', $t->tokenValue());
        $this->assertEquals('DIGIT', $t->token());
        $this->assertEquals('SPACE', $t->nextToken());

        $t->skipSpace();

        $this->assertEquals('PLUS', $t->token());
        $this->assertEquals('SPACE', $t->nextToken());
    }
}

