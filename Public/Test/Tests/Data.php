<?php

require_once('./Initialize.php');

class DataTest extends UnitTestCase {
	
	public function testPurify(){
		/* Common XSS-Attacks used by the XSS-Me Firefox Extension. On the right side 
		 * the expected output of the purifier, might not always be valid, but safe.
		 */
		$xss = array(
			'<meta http-equiv="refresh" content="0;url=javascript:document.vulnerable=true;">' => '',
			'<META HTTP-EQUIV="Set-Cookie" Content="USERID=<SCRIPT>document.vulnerable=true</SCRIPT>">' => '',
			'<SCRIPT>document.vulnerable=true;</SCRIPT>' => '',
			'<IMG SRC="jav ascript:document.vulnerable=true;">' => '',
			'<IMG SRC="javascript:document.vulnerable=true;">' => '',
			'<IMG SRC=" &#14; javascript:document.vulnerable=true;">' => '',
			'<BODY onload!#$%&()*~+_.,:;?@=document.vulnerable=true;>' => '',
			'<<SCRIPT>document.vulnerable=true;//<</SCRIPT>' => '&lt;',
			'<SCRIPT <B>document.vulnerable=true;</SCRIPT>' => '',
			'<IMG SRC="javascript:document.vulnerable=true;"' => '',
			'<iframe src="javascript:document.vulnerable=true; <' => '',
			'<SCRIPT>a=/XSS/\ndocument.vulnerable=true;</SCRIPT>' => '',
			'</TITLE><SCRIPT>document.vulnerable=true;</SCRIPT>' => '',
			'<INPUT TYPE="IMAGE" SRC="javascript:document.vulnerable=true;">' => '',
			'<BODY BACKGROUND="javascript:document.vulnerable=true;">' => '',
			'<BODY ONLOAD=document.vulnerable=true;>' => '',
			'<IMG DYNSRC="javascript:document.vulnerable=true;">' => '',
			'<IMG LOWSRC="javascript:document.vulnerable=true;">' => '',
			'<BGSOUND SRC="javascript:document.vulnerable=true;">' => '',
			'<BR SIZE="&{document.vulnerable=true}">' => '<br />',
			'<LAYER SRC="javascript:document.vulnerable=true;"></LAYER>' => '',
			'<LINK REL="stylesheet" HREF="javascript:document.vulnerable=true;">' => '',
			'<STYLE>li {list-style-image: url("javascript:document.vulnerable=true;");</STYLE><UL><LI>XSS' => '<ul><li>XSS</li></ul>',
			'<IFRAME SRC="javascript:document.vulnerable=true;"></IFRAME>' => '',
			'<FRAMESET><FRAME SRC="javascript:document.vulnerable=true;"></FRAMESET>' => '',
			'<TABLE BACKGROUND="javascript:document.vulnerable=true;">' => '<table></table>',
			'<TABLE><TD BACKGROUND="javascript:document.vulnerable=true;">' => '<table><td></td></table>',
			'<DIV STYLE="background-image: url(javascript:document.vulnerable=true;)">' => '<div></div>',
			'<DIV STYLE="background-image: url(&#1;javascript:document.vulnerable=true;)">' => '<div></div>',
			'<DIV STYLE="width: expression(document.vulnerable=true);">' => '<div></div>',
			"<STYLE>@im\port'\ja\vasc\ript:document.vulnerable=true';</STYLE>" => '',
			'<IMG STYLE="xss:expr/*XSS*/ession(document.vulnerable=true)">' => '',
			'<XSS STYLE="xss:expression(document.vulnerable=true)">' => '',
			'<STYLE TYPE="text/javascript">document.vulnerable=true;</STYLE>' => '',
			'<STYLE>.XSS{background-image:url("javascript:document.vulnerable=true");}</STYLE><A CLASS=XSS></A>' => '',
			'<STYLE type="text/css">BODY{background:url("javascript:document.vulnerable=true")}</STYLE>' => '',
			'<!--[if gte IE 4]><SCRIPT>document.vulnerable=true;</SCRIPT><![endif]-->' => '',
			'<BASE HREF="javascript:document.vulnerable=true;//">' => '',
			'<OBJECT classid=clsid:ae24fdae-03c6-11d1-8b76-0080c744f389><param name=url value=javascript:document.vulnerable=true></OBJECT>' => '',
			'<XML ID=I><X><C><![CDATA[<IMG SRC="javas]]<![CDATA[cript:document.vulnerable=true;">]]</C></X></xml><SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>' => '',
			'<XML ID="xss"><I><B><IMG SRC="javas<!-- -->cript:document.vulnerable=true"></B></I></XML><SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>' => '<span></span>',
			'<HTML><BODY><?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time"><?import namespace="t" implementation="#default#time2"><t:set attributeName="innerHTML" to="XSS<SCRIPT DEFER>document.vulnerable=true</SCRIPT>"></BODY></HTML>' => '',
			"<? echo('<SCR)';echo('IPT>document.vulnerable=true</SCRIPT>'); ?>" => '',
			'<HEAD><META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=UTF-7"></HEAD><SCRIPT>document.vulnerable=true;</SCRIPT>' => '',
			'<div onmouseover="document.vulnerable=true;">' => '<div></div>',
			'<img src="javascript:document.vulnerable=true;">' => '',
			'<img dynsrc="javascript:document.vulnerable=true;">' => '',
			'<input type="image" dynsrc="javascript:document.vulnerable=true;">' => '',
			'<bgsound src="javascript:document.vulnerable=true;">' => '',
			'&<script>document.vulnerable=true;</script>' => '&',
			'<link rel="stylesheet" href="javascript:document.vulnerable=true;">' => '',
			'<img src="mocha:document.vulnerable=true;">' => '',
			'<img src="livescript:document.vulnerable=true;">' => '',
			'<a href="about:<script>document.vulnerable=true;</script>">' => '',
			'<body onload="document.vulnerable=true;">' => '',
			'<div style="background-image: url(javascript:document.vulnerable=true;);">' => '<div></div>',
			'<div style="behaviour: url([link to code]);">' => '<div></div>',
			'<div style="binding: url([link to code]);">' => '<div></div>',
			'<div style="width: expression(document.vulnerable=true;);">' => '<div></div>',
			'<style type="text/javascript">document.vulnerable=true;</style>' => '',
			'<object classid="clsid:..." codebase="javascript:document.vulnerable=true;">' => '',
			'<style><!--</style><script>document.vulnerable=true;//--></script>' => '',
			'<<script>document.vulnerable=true;</script>' => '&lt;',
			'<![CDATA[<!--]]<script>document.vulnerable=true;//--></script>' => '',
			'<!-- -- --><script>document.vulnerable=true;</script><!-- -- -->' => '',
			'<img src="blah"onmouseover="document.vulnerable=true;">' => '<img src="blah" />', // We can't prevent that even if its ugly..
			'<img src="blah>" onmouseover="document.vulnerable=true;">' => '<img src="blah>" />',
			'<xml src="javascript:document.vulnerable=true;">' => '',
			'<xml id="X"><a><b><script>document.vulnerable=true;</script>;</b></a></xml>' => '',
			'<div datafld="b" dataformatas="html" datasrc="#X"></div>' => '<div></div>',
			"\xC0\xBCscript>document.vulnerable=true;\xC0\xBC/script>" => '&lt;script>document.vulnerable=true;&lt;/script>', // Yes, this is intended
			"<STYLE>@import'http://www.securitycompass.com/xss.css';</STYLE>" => '',
			'<META HTTP-EQUIV="Link" Content="<http://www.securitycompass.com/xss.css>; REL=stylesheet">' => '',
			'<STYLE>BODY{-moz-binding:url("http://www.securitycompass.com/xssmoz.xml#xss")}</STYLE>' => '',
			'<OBJECT TYPE="text/x-scriptlet" DATA="http://www.securitycompass.com/scriptlet.html"></OBJECT>' => '',
			'<HTML xmlns:xss><?import namespace="xss" implementation="http://www.securitycompass.com/xss.htc"><xss:xss>XSS</xss:xss></HTML>' => '',
			'<SCRIPT SRC="http://www.securitycompass.com/xss.jpg"></SCRIPT>' => '',
			'<SCRIPT a=">" SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<SCRIPT =">" SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<SCRIPT a=">" \'\' SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<SCRIPT "a=\'>\'" SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<SCRIPT a=`>` SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<SCRIPT a=">\'>" SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<SCRIPT>document.write("<SCRI");</SCRIPT>PT SRC="http://www.securitycompass.com/xss.js"></SCRIPT>' => '',
			'<div style="binding: url(http://www.securitycompass.com/xss.js);"> [Mozilla]' => '<div> [Mozilla]</div>',
		);
		
		foreach($xss as $x => $v)
			$this->assertEqual(Data::purify($x), $v);
	}
	
}