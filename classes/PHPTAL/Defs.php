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

// From http://dev.zope.org/Wikis/DevSite/Projects/ZPT/TAL%20Specification%201.4
//
// Order of Operations
//
// When there is only one TAL statement per element, the order in which
// they are executed is simple. Starting with the root element, each
// element's statements are executed, then each of its child elements is
// visited, in order, to do the same.
// 
// Any combination of statements may appear on the same elements, except
// that the content and replace statements may not appear together.
// 
// When an element has multiple statements, they are executed in this
// order:
// 
//     * define
//     * condition
//     * repeat
//     * content or replace
//     * attributes
//     * omit-tag
// 
// Since the on-error statement is only invoked when an error occurs, it
// does not appear in the list.
// 
// The reasoning behind this ordering goes like this: You often want to set
// up variables for use in other statements, so define comes first. The
// very next thing to do is decide whether this element will be included at
// all, so condition is next; since the condition may depend on variables
// you just set, it comes after define. It is valuable be able to replace
// various parts of an element with different values on each iteration of a
// repeat, so repeat is next. It makes no sense to replace attributes and
// then throw them away, so attributes is last. The remaining statements
// clash, because they each replace or edit the statement element.
// 
// If you want to override this ordering, you must do so by enclosing the
// element in another element, possibly div or span, and placing some of
// the statements on this new element. 
// 

/**
 * PHPTAL constants.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Defs
{
    // enumeration of attributes logic place relatively to the xml node
    const SURROUND = 1;
    const REPLACE = 2;
    const CONTENT = 3;

    /**
     * This array contains the list of all known attribute namespaces, if an
     * attribute belonging to one of this namespaces is not recognized by PHPTAL,
     * an exception will be raised.
     * 
     * These namespaces will be drop from resulting xml/xhtml unless the parser 
     * is told to keep them.
     */
    static $NAMESPACES = array('TAL', 'METAL', 'I18N', 'PHP', 'PHPTAL');

    /**
     * This dictionary contains ALL known PHPTAL attributes. Unknown attributes 
     * will be echoed in result as xhtml/xml ones.
     * 
     * The value define how and when the attribute handler will be called during
     * code generation.
     */ 
    static $DICTIONARY = array(
        'TAL:DEFINE'         => self::REPLACE,  // set a context variable
        'TAL:CONDITION'      => self::SURROUND, // print tag content only when condition true
        'TAL:REPEAT'         => self::SURROUND, // repeat over an iterable
        'TAL:CONTENT'        => self::CONTENT,  // replace tag content
        'TAL:REPLACE'        => self::REPLACE,  // replace entire tag
        'TAL:ATTRIBUTES'     => self::SURROUND, // dynamically set tag attributes
        'TAL:OMIT-TAG'       => self::SURROUND, // omit to print tag but not its content
        'TAL:COMMENT'        => self::SURROUND, // do nothing
        'TAL:ON-ERROR'       => self::SURROUND, // replace content with this if error occurs

        'METAL:DEFINE-MACRO' => self::SURROUND, // define a template macro
        'METAL:USE-MACRO'    => self::REPLACE,  // use a template macro
        'METAL:DEFINE-SLOT'  => self::SURROUND, // define a macro slot
        'METAL:FILL-SLOT'    => self::SURROUND, // fill a macro slot 

        'I18N:TRANSLATE'     => self::CONTENT,  // translate some data using GetText package
        'I18N:NAME'          => self::SURROUND, // prepare a translation name
        'I18N:ATTRIBUTES'    => self::SURROUND, // translate tag attributes values
        'I18N:DOMAIN'        => self::SURROUND, // choose translation domain

        'PHP:SET'            => self::REPLACE,
        'PHP:IF'             => self::SURROUND,
        'PHP:ELSEIF'         => self::SURROUND,
        'PHP:ELSE'           => self::SURROUND,
        'PHP:WHILE'          => self::SURROUND,
        'PHP:FOREACH'        => self::SURROUND,
        'PHP:CONTENT'        => self::CONTENT,
        'PHP:INCLUDE'        => self::REPLACE,
        'PHP:REPLACE'        => self::REPLACE,
        'PHP:ATTRIBUTES'     => self::REPLACE,
        'PHP:OMIT-TAG'       => self::SURROUND,       

        'PHPTAL:TALES'       => self::SURROUND,
        'PHPTAL:DEBUG'       => self::SURROUND,
    );

    /**
     * This rule associative array represents both ordering and exclusion 
     * mecanism for template attributes.
     *
     * All known attributes must appear here and must be associated with 
     * an occurence priority.
     *
     * When more than one phptal attribute appear in the same tag, they 
     * will execute in following order.
     */ 
    static $RULES_ORDER = array(
        'PHPTAL:DEBUG'       => -1,   // meta surround
        'PHPTAL:TALES'       => 0,    // meta surround
        
        'TAL:OMIT-TAG'       => 1,    // surround -> $tag->disableHeadFootPrint()
        'PHP:OMIT-TAG'       => 1,

        'TAL:ON-ERROR'       => 2,    // surround
        'I18N:DOMAIN'        => 3,    // surround

        'METAL:DEFINE-MACRO' => 4,    // surround
        'TAL:DEFINE'         => 4,    // replace
        'PHP:SET'            => 4,
        'I18N:NAME'          => 4,    // replace
        'I18N:TRANSLATE'     => 4,    // content

        'TAL:CONDITION'      => 5,    // surround
        'PHP:IF'             => 5,
        'PHP:ELSEIF'         => 5,
        'PHP:ELSE'           => 5,

        'TAL:REPEAT'         => 6,    // surround
        'PHP:WHILE'          => 6,
        'PHP:FOREACH'        => 6,

        'TAL:ATTRIBUTES'     => 7,    // replace
        'PHP:ATTRIBUTES'     => 7,
        'TAL:REPLACE'        => 7,    // replace
        'PHP:REPLACE'        => 7,
        'METAL:USE-MACRO'    => 7,    // replace
        'PHP:INCLUDE'        => 7,    // replace
        'METAL:DEFINE-SLOT'  => 7,    // replace
        'METAL:FILL-SLOT'    => 7,    // replace

        'I18N:ATTRIBUTES'    => 8,    // replace

        'TAL:CONTENT'        => 9,    // content
        'PHP:CONTENT'        => 9,

        'TAL:COMMENT'        => 10,    // surround
    );


    /**
     * This array contains XHTML tags that must be echoed in a &lt;tag/&gt; form
     * instead of the &lt;tag&gt;&lt;/tag&gt; form.
     *
     * In fact, some browsers does not support the later form so PHPTAL 
     * ensure these tags are correctly echoed.
     */
    static $XHTML_EMPTY_TAGS = array(
        'AREA',
        'BASE',
        'BASEFONT',
        'BR',
        'COL',
        'FRAME',
        'HR',
        'IMG',
        'INPUT',
        'ISINDEX',
        'LINK',
        'META',
        'PARAM',
    );

    /**
     * This array contains XHTML attributes that must be echoed in a minimized
     * form. Some browsers (non HTML4 compliants are unable to interpret those
     * attributes.
     *
     * The output will definitively not be an xml document !!
     * PreFilters should be set to modify xhtml input containing these attributes.
     */
    static $XHTML_BOOLEAN_ATTRIBUTES = array(
        'compact',
        'nowrap',
        'ismap',
        'declare',
        'noshade',
        'checked',
        'disabled',
        'readonly',
        'multiple',
        'selected',
        'noresize',
        'defer'
    );

    static function isEmptyTag( $tagName )
    {
        return in_array(strtoupper($tagName), self::$XHTML_EMPTY_TAGS);
    }
    
    /**
     * Returns true if the attribute is an xhtml boolean attribute.
     *
     * @return bool
     */
    static function isBooleanAttribute( $att )
    {
        return in_array($att, self::$XHTML_BOOLEAN_ATTRIBUTES);
    }

    /**
     * Returns true if the attribute is in the phptal dictionnary.
     *
     * @return bool
     */
    static function isPhpTalAttribute( $att )
    {
        return array_key_exists(strtoupper($att), self::$DICTIONARY);
    }
    
    /**
     * Returns true if the attribute is a valid phptal attribute or an unknown
     * attribute.
     *
     * Examples of valid attributes: tal:content, metal:use-slot
     * Examples of invalid attributes: tal:unknown, metal:content
     *
     * @return bool
     */
    static function isValidAttribute( $att )
    {
        if (preg_match('/(.*):(.*)/', $att, $m)) {
            list (,$ns,$sub) = $m;
            if (in_array(strtoupper($ns), self::$NAMESPACES) 
                && !self::isPhpTalAttribute($att)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if the attribute is a phptal handled xml namespace
     * declaration.
     *
     * Examples of handled xmlns:  xmlns:tal, xmlns:metal
     *
     * @return bool
     */
    static function isHandledXmlNs( $att )
    {
        $att = strtolower($att);
        return ($att == 'xmlns:tal' ||
                $att == 'xmlns:metal' ||
                $att == 'xmlns:i18n' ||
                $att == 'xmlns:phptal' ||
                $att == 'xmlns:php'
                );
    }
}

?>
