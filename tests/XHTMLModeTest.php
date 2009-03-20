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

require_once 'config.php';

class XHTMLModeTest extends PHPTAL_TestCase
{
    function testEmpty()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('<html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title tal:content="nonexistant | nothing" />
            <base href="http://example.com/"></base>
            <basefont face="Helvetica" />
            <meta name="test" content=""></meta>
            <link rel="test"></link>
        </head> 
        <body>
            <br/>
            <br />
            <br></br>
            <hr/>
            <img src="test"></img>
            <form>
                <textarea />
                <textarea tal:content="\'\'" />
                <textarea tal:content="nonexistant | nothing" />                
            </form>
        </body>
        </html>');
        $res = $tpl->execute();       
        $res = trim_string($res);
        $exp = trim_string('<html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <title></title>
                    <base href="http://example.com/" />
                    <basefont face="Helvetica" />
                    <meta name="test" content="" />
                    <link rel="test" />
                </head> 
                <body>
                    <br />
                    <br />
                    <br />
                    <hr />
                    <img src="test" />
                    <form>
                        <textarea></textarea>
                        <textarea></textarea>
                        <textarea></textarea>                
                    </form>
                </body>
                </html>');
        $this->assertEquals($exp, $res);
    }
    
    function testBoolean()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('
        <html xmlns="http://www.w3.org/1999/xhtml">
        <body>
            <input type="checkbox" checked="checked"></input>
            <input type="text" tal:attributes="readonly \'readonly\'"/>
            <input type="radio" tal:attributes="checked php:true; readonly \'readonly\'"/>
            <input type="radio" tal:attributes="checked php:false; readonly bogus | nothing"/>
            <select>
                <option selected="unexpected value"/>
                <option tal:repeat="n php:range(0,5)" tal:attributes="selected repeat/n/odd"/>
            </select>
            
            <script defer="defer"></script>
            <script tal:attributes="defer number:1"></script>
        </body>
        </html>');
        $res = $tpl->execute();       
        $res = trim_string($res);
        $exp = trim_string('<html xmlns="http://www.w3.org/1999/xhtml">
                <body>
                    <input type="checkbox" checked="checked" />
                    <input type="text" readonly="readonly" />
                    <input type="radio" checked="checked" readonly="readonly" />
                    <input type="radio" />
                    <select>
                        <option selected="selected"></option>
                        <option></option><option selected="selected"></option><option></option><option selected="selected"></option><option></option><option selected="selected"></option>            </select>

                    <script defer="defer"></script>
                    <script defer="defer"></script>
                </body>
                </html>');                
        $this->assertEquals($exp, $res);
   }
}

