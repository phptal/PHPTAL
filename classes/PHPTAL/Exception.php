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
 * @package phptal
 */
class PHPTAL_Exception extends Exception
{
    public $srcFile;
    public $srcLine;

    public function __construct($msg, $srcFile=false, $srcLine=false)
    {
        parent::__construct($msg);
        $this->srcFile = $srcFile;
        $this->srcLine = $srcLine;
    }

    public function __toString()
    {
        if (empty($this->srcFile)){
            return parent::__toString();
        }
        $res = sprintf('From %s around line %d'."\n", $this->srcFile, $this->srcLine);
        $res .= parent::__toString();
        return $res;
    }

    public static function formatted($format /*, ...*/)
    {
        $args = func_get_args();
        $msg  = call_user_func('sprintf', $args);
        return new PHPTAL_Exception($format);
    }
}

?>
