<?php
/*
 * Styx::Safehtml - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Parses HTML-Input and purifies/sanitizes/fixes bad input
 *
 * This Script is based on the Pear Package "HTML_Safe" but has been heavily
 * modified to be more secure and to suit the Styx Framework.
 * 
 * Remember: This Class is there to prevent Cross-Site-Scripting and may not always
 * produce valid output (in terms of W3C).
 * 
 * http://pear.php.net/package/HTML_Safe
 * 
 */

class Safehtml {
	public $_xhtml = '',
		$_counter = array(),
		$_stack = array(),
		$_dcCounter = array(),
		$_dcStack = array(),
		$_listScope = 0,
		$_liStack = array(),
		$_regexps = array(),
		
		
		$tagWhiteList = array(
			'a', 'br', 'img', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div',
			'sub', 'sup', 'table', 'tr', 'td', 'th', 'thead', 'tbody', 'tfood', 'ul',
			'ol', 'li', 'blockquote', 'address', 'pre', 'code', 'em', 'strong', 'i',
			'b', 'strike', 'span', 'ins', 'del', 'u', 's', 'big', 'small',
		),
		
		$convert = array(
			'font' => 'span',
		),
		
		$keepClasses = false,
		$allowedClasses = array(),
		
		$singleTags = array('area', 'br', 'img', 'input', 'hr', 'wbr', 'param'),
		$deleteTagsContent = array('textarea', 'script', 'style', 'title', 'xml'),
		$blackProtocols = array(
			'about', 'chrome', 'data', 'disk', 'hcp',  
			'help', 'javascript', 'livescript', 'lynxcgi', 'lynxexec', 
			'ms-help', 'ms-its', 'mhtml', 'mocha', 'opera',   
			'res', 'resource', 'shell', 'vbscript', 'view-source', 
			'vnd.ms.radio', 'wysiwyg',
		),
		$whiteProtocols = array(
			'ed2k', 'file', 'ftp', 'gopher', 'http', 'https', 
			'irc','mailto', 'news', 'nntp', 'telnet', 'webcal', 
			'xmpp', 'callto',
		),
		$protocolAttributes = array(
			'action', 'background', 'codebase', 'dynsrc', 'href', 'lowsrc', 'src', 
		),
		
		$cssKeywords = array(
			'z\-index', 'cursor', 'display', 'position ', 'absolute',
			'relative', 'behavior', 'behaviour', 'content', 'expression',
			'fixed', 'include-source', 'moz-binding', 'binding', 'scrollbar',
			'visibility', 'filter', 'opacity', 'accelerator', '-moz-',
			'azimuth', 'clip', 'counter', 'cue', 'elevation', 'ime-mode',
			'page', 'pause', 'pitch', 'link-source', 'ruby', 'richness',
			'zoom', 'volume', 'voice', 'unicode-bidi', '-webkit-',
		),
		
		$allowedAttributes = array(
			'title', 'class', 'width', 'height', 'type', 'src', 'alt', 'style',
			'rel', 'href', 'target', 'align', 'size', 'longdesc', 'border',
			'cellpadding', 'cellspacing', 'valign', 'nowrap', 'rowspan', 'colspan', 
		),
		
		/* This is a little addition to be *more* valid */
		$removeAttribIfNotInElement = array(
			'width' => array('object', 'img', 'hr', 'table', 'td', 'th'),
			'height' => array('object', 'img', 'hr', 'td', 'th'),
			'type' => array('li', 'ol', 'ul', 'a',),
			'src' => array('img'),
			'alt' => array('img'),
			'rel' => array('a'),
			'href' => array('a'),
			'target' => array('a'),
			'align' => array('div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img', 'hr', 'p', 'table', 'tr', 'td', 'th', 'tfood', 'thead', 'tbody'),
			'size' => array('hr'),
			'longdesc' => array('img'),
			'border' => array('table', 'img'),
			'cellpadding' => array('table'),
			'cellspacing' => array('table'),
			'valign' => array('table', 'tr', 'td', 'th', 'tfood', 'thead', 'tbody'),
			'nowrap' => array('table', 'tr', 'td', 'th', 'tfood', 'thead', 'tbody'),
			'rowspan' => array('tr', 'th'),
			'colspan' => array('tr', 'th'),
		),
		
		$removeIfNoAttribs = array('object', 'param', 'embed', 'img', 'a'),
		
		$removeIfEmpty = array('src', 'href'),
		
		$cleanup = array(
			'object' => array('width', 'height', 'type', 'classid', 'codebase'),
			'param' => array('name', 'value'),
			'embed' => array('src', 'type', 'wmode', 'width', 'height'),
		),
		
		$cleanupAttributes = array(
			'type' => array(
				'application/x-shockwave-flash',
				'application/x-director',
				'application/x-mplayer2',
				'video/quicktime',
				'audio/x-pn-realaudio-plugin'
			),
			'width' => 'id',
			'height' => 'id',
			'wmode' => array('transparent'),
			'name' => 'white',
			'value' => 'white',
			'src' => 'white',
			'classid' => array(
				'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000',
				'clsid:166b1bca-3f9c-11cf-8075-444553540000',
				'clsid:6bf52a52-394a-11d3-b153-00c04f79faa6',
				'clsid:02bf25d5-8c17-4b23-bc80-d3488abddc6b',
				'clsid:cfcdaa03-8be4-11cf-b84b-0020afbbccfa',
				'clsid:22d6f312-b0f6-11d0-94ab-0080c74c7e95',
				'clsid:05589fa1-c356-11ce-bf01-00aa0055595a',
			),
			'codebase' => array(
				'http://download.macromedia.com/',
				'http://activex.microsoft.com/',
				'http://www.apple.com/qtactivex/',
			)
		),
		
		$closeParagraph = array(
			'address', 'blockquote', 'center', 'dd', 'dir', 'div', 
			'dl', 'dt', 'h1', 'h2', 'h3', 'h4', 
			'h5', 'h6', 'hr', 'isindex', 'listing', 'marquee', 
			'menu', 'multicol', 'ol', 'p', 'plaintext', 'pre', 
			'table', 'ul', 'xmp', 
		),
		
		$tableTags = array('tbody', 'td', 'tfoot', 'th', 'thead', 'tr'),
		$listTags = array('dir', 'menu', 'ol', 'ul', 'dl');

	public function __construct($options = array(
		'video' => false,
		'classes' => false,
		'whitelist' => null,
	)){
		if(!empty($options['whitelist']) && is_array($options['whitelist']))
			$this->tagWhiteList = $options['whitelist'];
		
		if(!empty($options['video'])){
			$this->tagWhiteList[] = 'object';
			$this->tagWhiteList[] = 'embed';
			$this->tagWhiteList[] = 'param';
		}
		
		if(isset($options['classes']) && is_array($options['classes']))
			$this->allowedClasses = $options['classes'];
		else
			$this->keepClasses = isset($options['classes']);
		
		foreach($this->blackProtocols as $proto){
			$preg = '';
			
			for($i=0,$length=strlen($proto);$i<$length;$i++)
				$preg .= $proto[$i]."[\s\x01-\x1F]*";
			
			$this->_regexps[] = "/[\s\x01-\x1F]*".$preg.":/i";
		}
		
		foreach($this->cssKeywords as $css)
			$this->_regexps[] = '/'.$css.'/i';
		
		return true;
	}
	
	public function parse($doc){
		$doc = preg_replace("/<(?=[^a-zA-Z\/\!\?\%])/", '&lt;', $doc);
		$doc = str_replace("\x00", '', $doc);
		$doc = str_replace("\xC0\xBC", '&lt;', $doc);
		$doc = $this->repackUTF7($doc);
		
		$parser= new XML_HTMLSax3();
		$parser->set_object($this);
		$parser->set_element_handler('_openHandler', '_closeHandler');
		$parser->set_data_handler('_dataHandler');
		$parser->set_escape_handler('_escapeHandler');
		
		$parser->parse($doc);
		
		while($tag = array_pop($this->_stack))
			$this->_closeTag($tag);
		
		return $this->_xhtml;
	}
	
	public function _writeAttrs($attrs, $tag){
		$attributes = '';
		
		if(is_array($attrs)){
			foreach($attrs as $name => $value){
				$name = strtolower($name);
				$allow = false;
				if(!empty($this->cleanup[$tag])){
					if(empty($this->cleanupAttributes[$name]) || !in_array($name, $this->cleanup[$tag]))
						continue;
					
					if(is_array($this->cleanupAttributes[$name])){
						if($name=='codebase')
							foreach($this->cleanupAttributes[$name] as $base)
								if(String::starts($value, $base))
									$allow = true;
							
						if(in_array($value, $this->cleanupAttributes[$name]))
							$allow = true;
					}elseif($this->cleanupAttributes[$name]=='id'){
						$value = Data::id($value);
						if($value) $allow = true;
					}elseif($this->cleanupAttributes[$name]=='white'){
						$allow = true;
					}
					
					if(!$allow) continue;
				}
				
				if(!$allow && (strpos($name, 'on')===0 || strpos($name, 'data')===0 || !in_array($name, $this->allowedAttributes) || (!preg_match("/^[a-z0-9]+$/i", $name)) || (!empty($this->removeAttribIfNotInElement[$name]) && is_array($this->removeAttribIfNotInElement[$name]) && !in_array($tag, $this->removeAttribIfNotInElement[$name]))))
					continue;
				
				if($name=='class' && !$this->keepClasses){
					$new = array();
					
					$classes = explode(' ', $value);
					if(is_array($classes))
						foreach($classes as $class)
							if(in_array($class, $this->allowedClasses))
								$new[] = $class;
					
					if(count($new)) $value = implode(' ', $new);
					else continue;
				}
				
				if($value===true || is_null($value)) $value = $name;
				
				if($name=='style'){
					$value = str_replace("\\", '', $value);
					while(true){
						$_value = preg_replace("!/\*.*?\*/!s", '', $value);
						if($_value==$value) break;
						$value = $_value;
					}
					
					$value = str_replace('&', '&amp;', str_replace('&amp;', '&', $value));
					
					foreach($this->_regexps as $regex)
						if(preg_match($regex, $value))
							continue 2;
				}
				
				$tempval = preg_replace('/&#x([0-9a-f]+);?/mei', "chr(hexdec('\\1'))", preg_replace('/&#(\d+);?/me', "chr('\\1')", $value));
				
				if(in_array($name, $this->protocolAttributes) && strpos($tempval, ':')!==false){
					$_tempval = explode(':', $tempval);
					$proto = $_tempval[0];
					if(!in_array($proto, $this->whiteProtocols))
						continue;
				}
				
				if(!$value && in_array($name, $this->removeIfEmpty))
					continue;
				
				$attributes .= ' '.$name.'="'.str_replace('"', "&quot;", $value).'"';
			}
		}
		
		return $attributes;
	}
	
	public function _openHandler(&$parser, $name, $attrs){
		$name = strtolower($name);
		$attributes = null;
		
		if(in_array($name, $this->deleteTagsContent)){
			array_push($this->_dcStack, $name);
			$this->_dcCounter[$name] = isset($this->_dcCounter[$name]) ? $this->_dcCounter[$name]+1 : 1;
		}
		
		if(!empty($this->convert[$name])) $name = $this->convert[$name];
		
		if(count($this->_dcStack)!=0 || !in_array($name, $this->tagWhiteList))
			return true;
		
		if(!preg_match("/^[a-z0-9]+$/i", $name)){
			if(preg_match("!(?:\@|://)!i", $name))
				$this->_xhtml .= '&lt;'.$name.'&gt;';
			
			return true;
		}
		
		if(in_array($name, $this->singleTags)){
			$attributes = $this->_writeAttrs($attrs, $name);
			
			if(!$attributes && in_array($name, $this->removeIfNoAttribs))
				return true;
			
			$this->_xhtml .= '<'.$name.$attributes.' />';
			return true;
		}
		
		if(isset($this->_counter['table']) && $this->_counter['table']<=0 && in_array($name, $this->tableTags))
			return true;
		
		if(in_array($name, $this->closeParagraph) && in_array('p', $this->_stack))
			$this->_closeHandler($parser, 'p');
		
		if($name=='li' && count($this->_liStack) && $this->_listScope==$this->_liStack[count($this->_liStack)-1])
			$this->_closeHandler($parser, 'li');
		
		if(in_array($name, $this->listTags))
			$this->_listScope++;
		
		if($name=='li')
			array_push($this->_liStack, $this->_listScope);
		
		$attributes = $this->_writeAttrs($attrs, $name);
		if(!$attributes && in_array($name, $this->removeIfNoAttribs))
			return true;
		
		$this->_xhtml .= '<'.$name.$attributes.'>';
		array_push($this->_stack, $name);
		$this->_counter[$name] = isset($this->_counter[$name]) ? $this->_counter[$name]+1 : 1;
		
		return true;
	}
	
	public function _closeHandler($parser, $name){
		$name = strtolower($name);
		if(isset($this->_dcCounter[$name]) && ($this->_dcCounter[$name]>0) && (in_array($name, $this->deleteTagsContent))){
			while($name!=($tag = array_pop($this->_dcStack)))
				$this->_dcCounter[$tag]--;
			
			$this->_dcCounter[$name]--;
		}
		
		if(count($this->_dcStack)!=0 || !in_array($name, $this->tagWhiteList))
			return true;
		
		if(isset($this->_counter[$name]) && $this->_counter[$name]>0){
			while($name!=($tag = array_pop($this->_stack)))
				$this->_closeTag($tag);
			
			$this->_closeTag($name);
		}
		
		return true;
	}
	
	public function _closeTag($tag){
		$this->_xhtml .= '</'.$tag.'>';
		
		$this->_counter[$tag]--;
		if(in_array($tag, $this->listTags)) $this->_listScope--;
		
		if($tag=='li') array_pop($this->_liStack);
		
		return true;
	}
	
	public function _dataHandler($parser, $data){
		if(count($this->_dcStack)==0) $this->_xhtml .= $data;
		
		return true;
	}
	
	public function _escapeHandler($parser, $data){
		return true;
	}
	
	public function repackUTF7($str){
		return preg_replace_callback('!\+([0-9a-zA-Z/]+)\-!', array($this, 'repackUTF7Callback'), $str);
	}
	
	public function repackUTF7Callback($str){
		return preg_replace('/\x00(.)/', '$1', preg_replace_callback('/^((?:\x00.)*)((?:[^\x00].)+)/', array($this, 'repackUTF7Back'), base64_decode($str[1])));
	}
	
	public function repackUTF7Back($str){
		return $str[1].'+'.rtrim(base64_encode($str[2]), '=').'-';
	}
}


define('XML_HTMLSAX3_STATE_STOP', 0);
define('XML_HTMLSAX3_STATE_START', 1);
define('XML_HTMLSAX3_STATE_TAG', 2);
define('XML_HTMLSAX3_STATE_OPENING_TAG', 3);
define('XML_HTMLSAX3_STATE_CLOSING_TAG', 4);
define('XML_HTMLSAX3_STATE_ESCAPE', 6);
define('XML_HTMLSAX3_STATE_JASP', 7);
define('XML_HTMLSAX3_STATE_PI', 8);
class XML_HTMLSax3_StartingState  {
 function parse(&$context) {
  $data = $context->scanUntilString('<');
  if ($data != '') {
   $context->handler_object_data->
    {$context->handler_method_data}($context->htmlsax, $data);
  }
  $context->IgnoreCharacter();
  return XML_HTMLSAX3_STATE_TAG;
 }
}
class XML_HTMLSax3_TagState {
 function parse(&$context) {
  switch($context->ScanCharacter()) {
  case '/':
   return XML_HTMLSAX3_STATE_CLOSING_TAG;
   break;
  case '?':
   return XML_HTMLSAX3_STATE_PI;
   break;
  case '%':
   return XML_HTMLSAX3_STATE_JASP;
   break;
  case '!':
   return XML_HTMLSAX3_STATE_ESCAPE;
   break;
  default:
   $context->unscanCharacter();
   return XML_HTMLSAX3_STATE_OPENING_TAG;
  }
 }
}
class XML_HTMLSax3_ClosingTagState {
 function parse(&$context) {
  $tag = $context->scanUntilCharacters('/>');
  if ($tag != '') {
   $char = $context->scanCharacter();
   if ($char == '/') {
    $char = $context->scanCharacter();
    if ($char != '>') {
     $context->unscanCharacter();
    }
   }
   $context->handler_object_element->
    {$context->handler_method_closing}($context->htmlsax, $tag, FALSE);
  }
  return XML_HTMLSAX3_STATE_START;
 }
}
class XML_HTMLSax3_OpeningTagState {
 function parseAttributes(&$context) {
  $Attributes = array();
 
  $context->ignoreWhitespace();
  $attributename = $context->scanUntilCharacters("=/> \n\r\t");
  while ($attributename != '') {
   $attributevalue = NULL;
   $context->ignoreWhitespace();
   $char = $context->scanCharacter();
   if ($char == '=') {
    $context->ignoreWhitespace();
    $char = $context->ScanCharacter();
    if ($char == '"') {
     $attributevalue= $context->scanUntilString('"');
     $context->IgnoreCharacter();
    } else if ($char == "'") {
     $attributevalue = $context->scanUntilString("'");
     $context->IgnoreCharacter();
    } else {
     $context->unscanCharacter();
     $attributevalue =
      $context->scanUntilCharacters("> \n\r\t");
    }
   } else if ($char !== NULL) {
    $attributevalue = NULL;
    $context->unscanCharacter();
   }
   $Attributes[$attributename] = $attributevalue;
   
   $context->ignoreWhitespace();
   $attributename = $context->scanUntilCharacters("=/> \n\r\t");
  }
  return $Attributes;
 }

 function parse(&$context) {
  $tag = $context->scanUntilCharacters("/> \n\r\t");
  if ($tag != '') {
   $this->attrs = array();
   $Attributes = $this->parseAttributes($context);
   $char = $context->scanCharacter();
   if ($char == '/') {
    $char = $context->scanCharacter();
    if ($char != '>') {
     $context->unscanCharacter();
    }
    $context->handler_object_element->
     {$context->handler_method_opening}($context->htmlsax, $tag, 
     $Attributes, TRUE);
    $context->handler_object_element->
     {$context->handler_method_closing}($context->htmlsax, $tag, 
     TRUE);
   } else {
    $context->handler_object_element->
     {$context->handler_method_opening}($context->htmlsax, $tag, 
     $Attributes, FALSE);
   }
  }
  return XML_HTMLSAX3_STATE_START;
 }
}

class XML_HTMLSax3_EscapeState {
 function parse(&$context) {
  $char = $context->ScanCharacter();
  if ($char == '-') {
   $char = $context->ScanCharacter();
   if ($char == '-') {
    $context->unscanCharacter();
    $context->unscanCharacter();
    $text = $context->scanUntilString('-->');
    $text .= $context->scanCharacter();
    $text .= $context->scanCharacter();
   } else {
    $context->unscanCharacter();
    $text = $context->scanUntilString('>');
   }
  } else if ( $char == '[') {
   $context->unscanCharacter();
   $text = $context->scanUntilString(']>');
   $text.= $context->scanCharacter();
  } else {
   $context->unscanCharacter();
   $text = $context->scanUntilString('>');
  }

  $context->IgnoreCharacter();
  if ($text != '') {
   $context->handler_object_escape->
   {$context->handler_method_escape}($context->htmlsax, $text);
  }
  return XML_HTMLSAX3_STATE_START;
 }
}
class XML_HTMLSax3_JaspState {
 function parse(&$context) {
  $text = $context->scanUntilString('%>');
  if ($text != '') {
   $context->handler_object_jasp->
    {$context->handler_method_jasp}($context->htmlsax, $text);
  }
  $context->IgnoreCharacter();
  $context->IgnoreCharacter();
  return XML_HTMLSAX3_STATE_START;
 }
}
class XML_HTMLSax3_PiState {
 function parse(&$context) {
  $target = $context->scanUntilCharacters(" \n\r\t");
  $data = $context->scanUntilString('?>');
  if ($data != '') {
   $context->handler_object_pi->
   {$context->handler_method_pi}($context->htmlsax, $target, $data);
  }
  $context->IgnoreCharacter();
  $context->IgnoreCharacter();
  return XML_HTMLSAX3_STATE_START;
 }
}

class XML_HTMLSax3_Trim {
 public $orig_obj;
 public $orig_method;
 function XML_HTMLSax3_Trim(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function trimData(&$parser, $data) {
  $data = trim($data);
  if ($data != '') {
   $this->orig_obj->{$this->orig_method}($parser, $data);
  }
 }
}
class XML_HTMLSax3_CaseFolding {
 public $orig_obj;
 public $orig_open_method;
 public $orig_close_method;
 function XML_HTMLSax3_CaseFolding(&$orig_obj, $orig_open_method, $orig_close_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_open_method = $orig_open_method;
  $this->orig_close_method = $orig_close_method;
 }
 function foldOpen(&$parser, $tag, $attrs=array(), $empty = FALSE) {
  $this->orig_obj->{$this->orig_open_method}($parser, strtoupper($tag), $attrs, $empty);
 }
 function foldClose(&$parser, $tag, $empty = FALSE) {
  $this->orig_obj->{$this->orig_close_method}($parser, strtoupper($tag), $empty);
 }
}
class XML_HTMLSax3_Linefeed {
 public $orig_obj;
 public $orig_method;
 function XML_HTMLSax3_LineFeed(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData(&$parser, $data) {
  $data = explode("\n",$data);
  foreach ( $data as $chunk ) {
   $this->orig_obj->{$this->orig_method}($parser, $chunk);
  }
 }
}
class XML_HTMLSax3_Tab {
 public $orig_obj;
 public $orig_method;
 function XML_HTMLSax3_Tab(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData($parser, $data) {
  $data = explode("\t",$data);
  foreach ( $data as $chunk ) {
   $this->orig_obj->{$this->orig_method}($this, $chunk);
  }
 }
}
class XML_HTMLSax3_Entities_Parsed {
 public $orig_obj;
 public $orig_method;
 function XML_HTMLSax3_Entities_Parsed(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData($parser, $data) {
  $data = preg_split('/(&.+?;)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  foreach ( $data as $chunk ) {
   $chunk = html_entity_decode($chunk,ENT_NOQUOTES);
   $this->orig_obj->{$this->orig_method}($this, $chunk);
  }
 }
}

class XML_HTMLSax3_Entities_Unparsed {
 public $orig_obj;
 public $orig_method;
 function XML_HTMLSax3_Entities_Unparsed(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function breakData($parser, $data) {
  $data = preg_split('/(&.+?;)/',$data,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  foreach ( $data as $chunk ) {
   $this->orig_obj->{$this->orig_method}($this, $chunk);
  }
 }
}

class XML_HTMLSax3_Escape_Stripper {
 public $orig_obj;
 public $orig_method;
 function XML_HTMLSax3_Escape_Stripper(&$orig_obj, $orig_method) {
  $this->orig_obj =& $orig_obj;
  $this->orig_method = $orig_method;
 }
 function strip($parser, $data) {
  if ( substr($data,0,2) == '--' ) {
   $patterns = array(
    '/^\-\-/',    // Opening comment: --
    '/\-\-$/',    // Closing comment: --
   );
   $data = preg_replace($patterns,'',$data);

  } else if ( substr($data,0,1) == '[' ) {
   $patterns = array(
    '/^\[.*CDATA.*\[/s', // Opening CDATA
    '/\].*\]$/s',    // Closing CDATA
    );
   $data = preg_replace($patterns,'',$data);
  }

  $this->orig_obj->{$this->orig_method}($this, $data);
 }
}


class XML_HTMLSax3_StateParser {
 public $htmlsax,
	 $handler_object_element,
	 $handler_method_opening,
	 $handler_method_closing,
	 $handler_object_data,
	 $handler_method_data,
	 $handler_object_pi,
	 $handler_method_pi,
	 $handler_object_jasp,
	 $handler_method_jasp,
	 $handler_object_escape,
	 $handler_method_escape,
	 $handler_default,
	 $parser_options = array(),
	 $rawtext,
	 $position,
	 $length,
	 $State = array();

 function XML_HTMLSax3_StateParser (& $htmlsax) {
  $this->htmlsax = & $htmlsax;
  
  $this->State[XML_HTMLSAX3_STATE_START] = new XML_HTMLSax3_StartingState();

  $this->State[XML_HTMLSAX3_STATE_CLOSING_TAG] = new XML_HTMLSax3_ClosingTagState();
  $this->State[XML_HTMLSAX3_STATE_TAG] = new XML_HTMLSax3_TagState();
  $this->State[XML_HTMLSAX3_STATE_OPENING_TAG] = new XML_HTMLSax3_OpeningTagState();

  $this->State[XML_HTMLSAX3_STATE_PI] = new XML_HTMLSax3_PiState();
  $this->State[XML_HTMLSAX3_STATE_JASP] = new XML_HTMLSax3_JaspState();
  $this->State[XML_HTMLSAX3_STATE_ESCAPE] = new XML_HTMLSax3_EscapeState();
  
  $this->parser_options['XML_OPTION_TRIM_DATA_NODES'] = 0;
  $this->parser_options['XML_OPTION_CASE_FOLDING'] = 0;
  $this->parser_options['XML_OPTION_LINEFEED_BREAK'] = 0;
  $this->parser_options['XML_OPTION_TAB_BREAK'] = 0;
  $this->parser_options['XML_OPTION_ENTITIES_PARSED'] = 0;
  $this->parser_options['XML_OPTION_ENTITIES_UNPARSED'] = 0;
  $this->parser_options['XML_OPTION_STRIP_ESCAPES'] = 0;
 }

 function unscanCharacter() {
  $this->position -= 1;
 }

 function ignoreCharacter() {
  $this->position += 1;
 }

 function scanCharacter() {
  if ($this->position < $this->length) {
   return $this->rawtext{$this->position++};
  }
 }

 function scanUntilString($string) {
  $start = $this->position;
  $this->position = strpos($this->rawtext, $string, $start);
  if ($this->position === FALSE) {
   $this->position = $this->length;
  }
  return substr($this->rawtext, $start, $this->position - $start);
 }

 function scanUntilCharacters($string) {
	$startpos = $this->position;
	$length = strcspn($this->rawtext, $string, $startpos);
	$this->position += $length;
	return substr($this->rawtext, $startpos, $length);
 }

 function ignoreWhitespace() {
 	$this->position += strspn($this->rawtext, " \n\r\t", $this->position);
 }

 function parse($data) {
  if ($this->parser_options['XML_OPTION_TRIM_DATA_NODES']==1) {
   $decorator = new XML_HTMLSax3_Trim(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'trimData';
  }
  if ($this->parser_options['XML_OPTION_CASE_FOLDING']==1) {
   $open_decor = new XML_HTMLSax3_CaseFolding(
    $this->handler_object_element,
    $this->handler_method_opening,
    $this->handler_method_closing);
   $this->handler_object_element =& $open_decor;
   $this->handler_method_opening ='foldOpen';
   $this->handler_method_closing ='foldClose';
  }
  if ($this->parser_options['XML_OPTION_LINEFEED_BREAK']==1) {
   $decorator = new XML_HTMLSax3_Linefeed(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  if ($this->parser_options['XML_OPTION_TAB_BREAK']==1) {
   $decorator = new XML_HTMLSax3_Tab(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  if ($this->parser_options['XML_OPTION_ENTITIES_UNPARSED']==1) {
   $decorator = new XML_HTMLSax3_Entities_Unparsed(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  if ($this->parser_options['XML_OPTION_ENTITIES_PARSED']==1) {
   $decorator = new XML_HTMLSax3_Entities_Parsed(
    $this->handler_object_data,
    $this->handler_method_data);
   $this->handler_object_data =& $decorator;
   $this->handler_method_data = 'breakData';
  }
  // Note switched on by default
  if ($this->parser_options['XML_OPTION_STRIP_ESCAPES']==1) {
   $decorator = new XML_HTMLSax3_Escape_Stripper(
    $this->handler_object_escape,
    $this->handler_method_escape);
   $this->handler_object_escape =& $decorator;
   $this->handler_method_escape = 'strip';
  }
  $this->rawtext = $data;
  $this->length = strlen($data);
  $this->position = 0;
  $this->_parse();
 }

 function _parse($state = XML_HTMLSAX3_STATE_START) {
  do {
   $state = $this->State[$state]->parse($this);
  } while ($state != XML_HTMLSAX3_STATE_STOP &&
     $this->position < $this->length);
 }
}

class XML_HTMLSax3_NullHandler {
 function DoNothing() {
 }
}

class XML_HTMLSax3 {
 public $state_parser;

 function XML_HTMLSax3() {
  $this->state_parser = new XML_HTMLSax3_StateParser($this);
  
  $nullhandler = new XML_HTMLSax3_NullHandler();
  $this->set_object($nullhandler);
  $this->set_element_handler('DoNothing', 'DoNothing');
  $this->set_data_handler('DoNothing');
  $this->set_pi_handler('DoNothing');
  $this->set_jasp_handler('DoNothing');
  $this->set_escape_handler('DoNothing');
 }

 function set_object(&$object) {
  if ( is_object($object) ) {
   $this->state_parser->handler_default =& $object;
   return true;
  }
 }

 function set_option($name, $value=1) {
  if ( array_key_exists($name,$this->state_parser->parser_options) ) {
   $this->state_parser->parser_options[$name] = $value;
   return true;
  }
 }

 function set_data_handler($data_method) {
  $this->state_parser->handler_object_data =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_data = $data_method;
 }

 function set_element_handler($opening_method, $closing_method) {
  $this->state_parser->handler_object_element =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_opening = $opening_method;
  $this->state_parser->handler_method_closing = $closing_method;
 }

 function set_pi_handler($pi_method) {
  $this->state_parser->handler_object_pi =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_pi = $pi_method;
 }

 function set_escape_handler($escape_method) {
  $this->state_parser->handler_object_escape =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_escape = $escape_method;
 }

 function set_jasp_handler ($jasp_method) {
  $this->state_parser->handler_object_jasp =& $this->state_parser->handler_default;
  $this->state_parser->handler_method_jasp = $jasp_method;
 }

 function get_current_position() {
  return $this->state_parser->position;
 }

 function get_length() {
  return $this->state_parser->length;
 }

 function parse($data) {
  $this->state_parser->parse($data);
 }
}
?>
