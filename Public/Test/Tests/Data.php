<?php

require_once('./Initialize.php');

class DataTest extends UnitTestCase {
	
	public function testEscape(){
		// This makes sure $ is escaped so ${blah} won't be replaced/removed by the template
		// (This is customizable in an application and it can be removed if there is a custom regex for the templates)
		$this->assertEqual(Data::escape('Te$t'), 'Te&#36;t');
	}
	
	public function testSanitize(){
		$this->assertEqual(Data::sanitize(' <Te$t> '), '&lt;Te&#36;t&gt;');
	}
	
	public function testId(){
		$this->assertEqual(Data::id(5), 5);
		
		$this->assertEqual(Data::id('5'), 5);
		
		$this->assertEqual(Data::id(' 5 '), 0);
		
		$this->assertEqual(Data::id(10, 5), 10);
		$this->assertEqual(Data::id(11, 5), 10);
		$this->assertEqual(Data::id(14, 5), 10);
		$this->assertEqual(Data::id(15, 5), 15);
		
		$this->assertEqual(Data::id('5a'), 0);
		$this->assertEqual(Data::id('a5'), 0);
	}
	
	public function testBool(){
		$this->assertFalse(Data::bool('false'));
		$this->assertFalse(Data::bool(false));
		$this->assertFalse(Data::bool(null));
		$this->assertFalse(Data::bool(0));
		
		$this->assertTrue(Data::bool('true'));
		$this->assertTrue(Data::bool(true));
		$this->assertTrue(Data::bool(1));
		$this->assertTrue(Data::bool(100));
		$this->assertTrue(Data::bool('anything'));
	}
	
	public function testNumericrange(){
		$this->assertEqual(Data::numericrange(5, array(1, 10)), 5);
		
		$this->assertEqual(Data::numericrange(15, array(1, 10)), 0);
		
		$this->assertEqual(Data::numericrange('a', array(1, 10)), 0);
		
		$this->assertEqual(Data::numericrange(5, array(5, 10)), 5);
		$this->assertEqual(Data::numericrange(5, array(6, 10)), 0);
	}
	
	public function testDate(){
		$this->assertEqual(Data::date('12.01.2009'), 1231714800);
		
		$this->assertEqual(Data::date('01.12.2009', array('order' => 'mdy')), 1231714800);
		
		$this->assertNull(Data::date('01/12/2009', array('order' => 'mdy')));
		$this->assertEqual(Data::date('01/12/2009', array('order' => 'mdy', 'separator' => '/')), 1231714800);
		
		$day = date('j', time()+86400);
		$month = date('n', time()+86400);
		$year = date('Y', time()+86400);
		$tomorrow = mktime(0, 0, 0, $month, $day, $year);
		
		$this->assertNull(Data::date($day.'.'.$month.'.'.$year));
		$this->assertEqual(Data::date($day.'.'.$month.'.'.$year, array('future' => true)), $tomorrow);
		$this->assertEqual(Data::date('12.01.2009', array('future' => true)), 1231714800);
	}
	
	public function testUrl(){
		$this->assertNull(Data::url('http'));
		
		$this->assertNull(Data::url('http://'));
		
		$this->assertEqual(Data::url('http://og5.net'), 'http://og5.net');
		
		$this->assertEqual(Data::url('http://og5.net/test/path'), 'http://og5.net/test/path');
		
		$this->assertEqual(Data::url('http://og5.net/t<>est'), 'http://og5.net/t&lt;&gt;est');
	}
	
	public function testExcerpt(){
		$this->assertEqual(Data::excerpt('Hello World', array('length' => 3)), 'Hel...');
		
		$this->assertEqual(Data::excerpt('Hello World', array('length' => 3, 'dots' => false)), 'Hel');
		
		$this->assertEqual(Data::excerpt('<b>Hello World</b>', array('length' => 6, 'dots' => false)), '<b>Hel</b>');
		$this->assertEqual(Data::excerpt('<b>Hello World</b>', array('length' => 2, 'dots' => false)), '<b></b>');
		$this->assertEqual(Data::excerpt('<b>Hello World</b>', array('length' => 1, 'dots' => false)), '');
	}
	
	public function testEncode(){
		$array = array('test' => "js\ton");
		
		$this->assertEqual(Data::encode($array), '{"test":"json"}');
		
		// Data::encode strips out empty values and typecasts numbers
		$this->assertEqual(Data::encode(array(
			'null' => 0,
			'int' => 3,
			'strint' => '31',
			'float' => 1.03,
			'strfloat' => '1.03',
			'string' => 'Test',
			'nil' => null,
			'remove' => ' ',
			'clean' => "Te\nst",
		)), json_encode(array(
			'null' => 0,
			'int' => 3,
			'strint' => 31,
			'float' => 1.03,
			'strfloat' => 1.03,
			'string' => 'Test',
			'clean' => "Te st",
		)));
	}
	
	public function testPagetitle(){
		$this->assertEqual(Data::pagetitle('Täst'), 'Taest');
		$this->assertEqual(Data::pagetitle('Täöst'), 'Taeoest');
		
		$this->assertEqual(Data::pagetitle('Te   st'), 'Te_st');
		$this->assertEqual(Data::pagetitle('Test!§""§"'), 'Test');
		$this->assertEqual(Data::pagetitle('^$§$Test!§""§"'), 'Test');
		
		$this->assertEqual(Data::pagetitle('Who\'s "this"?'), 'Whos_this');
		
		$options = array(
			'identifier' => array(
				'internal' => 'int',
				'external' => 'ext',
			),
			'contents' => array(
				array('int' => 1, 'ext' => 'Test_1'),
				array('int' => 2, 'ext' => 'Test_2'),
				array('int' => 3, 'ext' => 'Test_3'),
				array('int' => 4, 'ext' => 'Test_4'),
				array('int' => 5, 'ext' => 'Test'),
				array('int' => 6, 'ext' => 'Test_5'),
				array('int' => 7, 'ext' => 'Test_6'),
				array('int' => 8, 'ext' => 'Test_7'),
				array('int' => 9, 'ext' => 'Test_8'),
				array('int' => 10, 'ext' => 'Test_9'),
			),
		);
		
		// We test the contents if we add a new one
		$this->assertEqual(Data::pagetitle('Test', $options), 'Test_10');
		
		$this->assertEqual(Data::pagetitle('Test"§', $options), 'Test_10');
		
		// We produce a pagetitle while we try to "edit" the content with int => 5
		$options['id'] = 5;
		
		$this->assertEqual(Data::pagetitle('Test', $options), 'Test');
		
		$this->assertEqual(Data::pagetitle('Test"§', $options), 'Test');
		
		$this->assertEqual(Data::pagetitle('Different', $options), 'Different');
		
		$options = array(
			'contents' => array(
				'Test_1',
				'Test_2',
				'Test_3',
				'Test_4',
				'Test_5',
				'Test_6',
				'Test_7',
			),
		);
		
		$this->assertEqual(Data::pagetitle('Test', $options), 'Test');
		
		$options['contents'][] = 'Test';
		
		$this->assertEqual(Data::pagetitle('Test', $options), 'Test_8');
	}
	
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