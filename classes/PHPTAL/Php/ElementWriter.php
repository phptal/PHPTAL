<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

/**
 * @package phptal.php
 */
class PHPTAL_Php_ElementWriter
{
    public function __construct(PHPTAL_Php_CodeWriter $writer, PHPTAL_Php_Element $tag)
    {
        $this->_writer = $writer;
        $this->_tag = $tag;
    }

    public function writeHead()
    {
        if ($this->_tag->headFootDisabled)
            return;
        
        if ($this->_tag->headFootPrintCondition){
            $this->_writer->doIf($this->_tag->headFootPrintCondition);
        }

        $this->_writer->pushHtml('<'.$this->_tag->name);
        $this->_writeAttributes();

        if ($this->_tag->isEmptyNode()){
            $this->_writer->pushHtml('/>');
        }
        else {
            $this->_writer->pushHtml('>');
        }

        if ($this->_tag->headFootPrintCondition){
            $this->_writer->doEnd();
        }

    }

    public function writeFoot()
    {
        if ($this->_tag->headFootDisabled)
            return;
        if ($this->_tag->isEmptyNode())
            return;

        if ($this->_tag->headFootPrintCondition){
            $this->_writer->doIf($this->_tag->headFootPrintCondition);
        }

        $this->_writer->pushHtml('</'.$this->_tag->name.'>');

        if ($this->_tag->headFootPrintCondition){
            $this->_writer->doEnd();
        }
    }

    public function writeAttributes()
    {
        $fullreplaceRx = PHPTAL_Php_Attribute_TAL_Attributes::REGEX_FULL_REPLACE;
        foreach ($this->_tag->attributes as $key=>$value) {
            if (preg_match($fullreplaceRx, $value)){
                $this->_writer->pushHtml($value);
            }
            /*
            else if (strpos('<?php', $value) === 0){
                $this->_writer->pushHtml(' '.$key.'="');
                $this->_writer->pushRawHtml($value);
                $this->_writer->pushHtml('"');
            }
            */
            else {
                $this->_writer->pushHtml(' '.$key.'="'.$value.'"');
            }
        }
    }

    private $_tag;
    private $_writer;
}

?>
