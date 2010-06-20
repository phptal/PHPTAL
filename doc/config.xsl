<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"

                xmlns="http://www.w3.org/1999/xhtml"
                version="1.0">

<xsl:template match="example" mode="object.title.markup">
	<xsl:param name="allow-anchors" select="0"/>
  <xsl:call-template name="substitute-markup">
    <xsl:with-param name="allow-anchors" select="$allow-anchors"/>
    <xsl:with-param name="template" select="'%t'"/>
  </xsl:call-template>
</xsl:template>

  <xsl:param name="formal.object.break.after" select="0"/>
  <xsl:param name="admon.style" select="''"/>

	<xsl:template name="nongraphical.admonition">
	  <div class="{name(.)}">
	    <h4 class="title">
	      <xsl:call-template name="anchor"/>
	      <xsl:if test="$admon.textlabel != 0 or title">
	        <xsl:apply-templates select="." mode="object.title.markup"/>
	      </xsl:if>
	    </h4>
	    <xsl:apply-templates/>
	  </div>
	</xsl:template>

  <xsl:param name="html.stylesheet" select="'/nifty.css'"/>
  <xsl:param name="html.extra.head.links" select="1"></xsl:param>
  <xsl:param name="navig.showtitles">1</xsl:param>

  <xsl:param name="header.rule" select="0"></xsl:param>
  <xsl:param name="footer.rule" select="0"></xsl:param>

  <xsl:param name="toc.list.type">ol</xsl:param>
  <xsl:param name="toc.section.depth">5</xsl:param>
  <xsl:param name="annotate.toc" select="0"></xsl:param>

  <xsl:param name="generate.revhistory.link" select="1"></xsl:param>
  <xsl:param name="generate.id.attributes" select="1"></xsl:param>

  <xsl:param name="chapter.autolabel" select="0"></xsl:param>
  <xsl:param name="part.autolabel" select="0"></xsl:param>
  <xsl:param name="section.autolabel" select="0"></xsl:param>
  <xsl:param name="section.autolabel.max.depth">0</xsl:param>

  <xsl:param name="generate.section.toc.level" select="1"></xsl:param>
  <xsl:param name="chunk.section.depth" select="2"></xsl:param>
  <xsl:param name="chunk.first.sections" select="1"></xsl:param>

  <xsl:param name="table.borders.with.css" select="1"></xsl:param>
  <xsl:param name="points.per.em">16</xsl:param>

  <xsl:param name="highlight.source" select="1"></xsl:param>
  <xsl:param name="highlight.default.language">xml</xsl:param>

  <xsl:param name="chunker.output.omit-xml-declaration">yes</xsl:param>
  <xsl:param name="chunker.output.doctype-public">-//W3C//DTD XHTML 1.0 Strict//EN</xsl:param>
  <xsl:param name="chunker.output.doctype-system">http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd</xsl:param>
  <xsl:param name="html.cleanup" select="1"></xsl:param>

  <xsl:param name="use.id.as.filename" select="1"/>


  <xsl:template name="header.navigation">
    <xsl:param name="prev" select="/foo"/>
    <xsl:param name="next" select="/foo"/>
    <xsl:param name="nav.context"/>

    <xsl:variable name="home" select="/*[1]"/>
    <xsl:variable name="up" select="parent::*"/>

    <xsl:variable name="row1" select="$navig.showtitles != 0"/>
    <xsl:variable name="row2" select="count($prev) &gt; 0                                     or (count($up) &gt; 0                                          and generate-id($up) != generate-id($home)                                         and $navig.showtitles != 0)                                     or count($next) &gt; 0"/>

    <xsl:if test="$suppress.navigation = '0' and $suppress.header.navigation = '0'">
      <div class="navheader">
        <xsl:if test="$row1 or $row2">
          <table width="100%" summary="Navigation header">

            <xsl:if test="$row2">
              <tr>
                <td>
                  <xsl:if test="count($prev)&gt;0">
                    <a rel="prev">
                      <xsl:attribute name="href">
                        <xsl:call-template name="href.target">
                          <xsl:with-param name="object" select="$prev"/>
                        </xsl:call-template>
                      </xsl:attribute>
                      <xsl:call-template name="navig.content">
                        <xsl:with-param name="direction" select="'prev'"/>
                      </xsl:call-template>
                    </a>
                  </xsl:if>
                </td>
                <th>
                  <xsl:call-template name="breadcrumbs"/>
                </th>
                <td align="right">
                  <xsl:if test="count($next)&gt;0">
                    <a rel="next">
                      <xsl:attribute name="href">
                        <xsl:call-template name="href.target">
                          <xsl:with-param name="object" select="$next"/>
                        </xsl:call-template>
                      </xsl:attribute>
                      <xsl:call-template name="navig.content">
                        <xsl:with-param name="direction" select="'next'"/>
                      </xsl:call-template>
                    </a>
                  </xsl:if>
                </td>
              </tr>
            </xsl:if>
          </table>
        </xsl:if>

      </div>
    </xsl:if>
  </xsl:template>

  <xsl:template name="user.header.navigation">
    <div id="header"><div>
      <h1><a href="/"><abbr>PHPTAL</abbr> PHP Template Attribute Language</a></h1>
    </div>
      </div>
      <div id="menu">
      <ul>
          <li class="news"><a href="/">News</a></li>
          <li class="introduction"><a href="/introduction.html">Introduction</a></li>
          <li class="download"><a href="/download.html">Download</a></li>
          <li class="current manuals"><a href="/manuals.html">Manuals</a></li>
          <li class="faq">     <a href="/faq.html">FAQ</a></li>
          <li class="contact"> <a href="/contact.html">Mailinglist</a></li>
      </ul>
      </div>



 </xsl:template>

<xsl:template name="apply-highlighting">
  <!--
    xslthl = saxon = java = pain, and if that wasn't enough, docbook-xsl drops language attribute.
  -->
  <code>
    <xsl:if test="@language">
      <xsl:attribute name="class">
				<xsl:value-of select="@language"/>
			</xsl:attribute>
    </xsl:if>
    <xsl:apply-templates/>
  </code>
</xsl:template>


 <xsl:template name="breadcrumbs">
   <xsl:param name="this.node" select="."/>

   <xsl:variable name="ancestors" select="$this.node/ancestor::*" />

   <xsl:if test="count($ancestors) &gt; 0">
   <div class="breadcrumbs">
     <xsl:for-each select="$ancestors">
       <xsl:if test="not(position() = 1) or count($ancestors) = 1">
       <span class="breadcrumb-link">
         <a>
           <xsl:attribute name="href">
             <xsl:call-template name="href.target">
               <xsl:with-param name="object" select="."/>
               <xsl:with-param name="context" select="$this.node"/>
             </xsl:call-template>
           </xsl:attribute>
           <xsl:apply-templates select="." mode="title.markup"/>
         </a>
       </span>
       <xsl:text> › </xsl:text>
       </xsl:if>
     </xsl:for-each>
     <!-- And display the current node, but not as a link -->
     <span class="breadcrumb-node">
       <xsl:apply-templates select="$this.node" mode="title.markup"/>
     </span>
   </div>
   </xsl:if>
 </xsl:template>

</xsl:stylesheet>
