<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
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

require_once 'PHPTAL/Attribute/TAL/Comment.php';
require_once 'PHPTAL/Attribute/TAL/Replace.php';
require_once 'PHPTAL/Attribute/TAL/Content.php';
require_once 'PHPTAL/Attribute/TAL/Condition.php';
require_once 'PHPTAL/Attribute/TAL/Attributes.php';
require_once 'PHPTAL/Attribute/TAL/Repeat.php';
require_once 'PHPTAL/Attribute/TAL/Define.php';
require_once 'PHPTAL/Attribute/TAL/OnError.php';
require_once 'PHPTAL/Attribute/TAL/OmitTag.php';

require_once 'PHPTAL/Attribute/METAL/DefineMacro.php';
require_once 'PHPTAL/Attribute/METAL/UseMacro.php';
require_once 'PHPTAL/Attribute/METAL/DefineSlot.php';
require_once 'PHPTAL/Attribute/METAL/FillSlot.php';

require_once 'PHPTAL/Attribute/PHPTAL/Tales.php';
require_once 'PHPTAL/Attribute/PHPTAL/Debug.php';
require_once 'PHPTAL/Attribute/PHPTAL/Id.php';

require_once 'PHPTAL/Attribute/I18N/Translate.php';
require_once 'PHPTAL/Attribute/I18N/Name.php';
require_once 'PHPTAL/Attribute/I18N/Domain.php';
require_once 'PHPTAL/Attribute/I18N/Attributes.php';

/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Attribute 
{
    public $name;
    public $expression;
    public $tag;

    public function __construct( $tag )
    {
        $this->tag = $tag;
    }

    public abstract function start();
    public abstract function end();
    
    public static function createAttribute( $tag, $attName, $expression )
    {
        $class = 'PHPTAL_Attribute_' . str_replace(':','_', $attName);
        $class = str_replace('-', '', $class);
        
        $result = new $class($tag);
        $result->name = $attName;
        $result->expression = $expression;
        return $result;
    }
}

?>
