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
//  Authors: Kornel Lesiński <kornel@aardvarkmedia.co.uk>
//  

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

/** phptal:cache (note that's not tal:cache) caches element's HTML for a given time. Time is a number with 'd', 'h', 'm' or 's' suffix.
    There's optional parameter that defines how cache should be shared. By default cache is not sensitive to template's context at all 
    - it's shared between all pages that use that template. 
    You can add per url to have separate copy of given element for every URL.
    
    You can add per expression to have different cache copy for every different value of an expression (which MUST evaluate to a string). 
    Expression cannot refer to variables defined using tal:define on the same element.

    NB:
    * phptal:cache blocks can be nested, but outmost block will cache other blocks regardless of their freshness.
    * you cannot use metal:fill-slot inside elements with phptal:cache

    Examples:
    <div phptal:cache="3h">...</div> <!-- <div> to be evaluated at most once per 3 hours. -->
    <ul phptal:cache="1d per object/id">...</ul> <!-- <ul> be cached for one day, separately for each object. -->
*/
class PHPTAL_Php_Attribute_PHPTAL_Cache extends PHPTAL_Php_Attribute
{  
    private $cache_tag;

    public function start()
    {
        if (!preg_match('/^\s*([0-9]+\s*|[a-zA-Z][a-zA-Z0-9_]*\s+)([dhms])\s*(?:\;?\s*per\s+([^;]+)|)\s*$/',$this->expression, $matches))
            throw new PHPTAL_ParserException("Cache attribute syntax error: ".$this->expression);
            
        $cache_len = $matches[1];
        if (!is_numeric($cache_len)) $cache_len = '$ctx->'.$cache_len;
        switch($matches[2])
        {
            case 'd': $cache_len .= '*24'; /* no break */
            case 'h': $cache_len .= '*60'; /* no break */
            case 'm': $cache_len .= '*60'; /* no break */
        }

        $this->cache_tag = '"'.addslashes( $this->tag->node->getName() . ':' . $this->tag->node->getSourceLine()).'"';
        
        $cache_per_expression = isset($matches[3])?trim($matches[3]):NULL;
        if ($cache_per_expression == 'url')
        {
            $this->cache_tag .= '.$_SERVER["REQUEST_URI"]';
        }
        else if ($cache_per_expression == 'nothing') {  }
        else if ($cache_per_expression)
        {
             $code = $this->tag->generator->evaluateExpression($cache_per_expression);

             if (is_array($code)) { throw new PHPTAL_ParserException("Chained expressions in per-cache directive are not supported"); }
            
             $old_cache_tag = $this->cache_tag;
             $this->cache_tag = '$ctx->cache_tag_';
             $this->tag->generator->doSetVar($this->cache_tag, '('.$code.')."@".' . $old_cache_tag );
        }
    
	    $cond = '!file_exists(__FILE__.md5('.$this->cache_tag.')) || time() - '.$cache_len.' >= @filemtime(__FILE__.md5('.$this->cache_tag.'))';

        $this->tag->generator->doIf($cond);
        $this->tag->generator->doEval('ob_start()');
    }

    public function end()
    {
        $this->tag->generator->doEval('file_put_contents(__FILE__.md5('.$this->cache_tag.'), ob_get_flush())');
        $this->tag->generator->doElse();
        $this->tag->generator->doEval('readfile(__FILE__.md5('.$this->cache_tag.'))');
        $this->tag->generator->doEnd();
    }
}

