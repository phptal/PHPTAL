<?php

// i18n:attributes
//
// This attribute will allow us to translate attributes of HTML tags, such 
// as the alt attribute in the img tag. The i18n:attributes attribute 
// specifies a list of attributes to be translated with optional message 
// IDs? for each; if multiple attribute names are given, they must be 
// separated by semi-colons. Message IDs? used in this context must not 
// include whitespace.
//
// Note that the value of the particular attributes come either from the 
// HTML attribute value itself or from the data inserted by tal:attributes.
//
// If an attibute is to be both computed using tal:attributes and translated, 
// the translation service is passed the result of the TALES expression for 
// that attribute.
//
// An example:
//
//     <img src="http://foo.com/logo" alt="Visit us"
//              tal:attributes="alt here/greeting"
//              i18n:attributes="alt"
//              />
//
//
// In this example, let tal:attributes set the value of the alt attribute to 
// the text "Stop by for a visit!". This text will be passed to the 
// translation service, which uses the result of language negotiation to 
// translate "Stop by for a visit!" into the requested language. The example 
// text in the template, "Visit us", will simply be discarded.
//
// Another example, with explicit message IDs:
//
//   <img src="../icons/uparrow.png" alt="Up"
//        i18n:attributes="src up-arrow-icon; alt up-arrow-alttext"
//   >
//
// Here, the message ID up-arrow-icon will be used to generate the link to 
// an icon image file, and the message ID up-arrow-alttext will be used for 
// the "alt" text.
//
class PHPTAL_Attribute_I18N_Attributes extends PHPTAL_Attribute
{
}

?>
