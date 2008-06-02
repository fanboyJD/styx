<?php
class PackageManager {
	
	private static $Directories = array(
			'js' => '../JavaScript',
			'css' => '../Css',
		),
		$Elements = array(
			'js' => array(
				'attribute' => 'src',
				'options' => array(
					':tag' => 'script',
					':unknown' => true,
					'type' => 'text/javascript',
				),
			),
			'css' => array(
				'attribute' => 'href',
				'options' => array(
					':tag' => 'link',
					':unknown' => true,
					':standalone' => true,
					'rel' => 'stylesheet',
					'media' => 'all',
					'type' => 'text/css',
				),
			),
		),
		$Packages = array(),
		$Package = null,
		$compress = null,
		$encoding = null;
	
	public static function add($name, $options = array(
		'type' => '',
		'files' => array(),
		'options' => array(),
	)){
		splat($options['files']);
		
		self::$Packages[$name] = $options;
	}
	
	public static function has($name){
		return is_array(self::$Packages[$name]);
	}
	
	public static function setPackage($name){
		if(self::has($name)) self::$Package = $name;
	}
	
	public static function getType(){
		return self::$Packages[self::$Package]['type'];
	}
	
	public static function assignToMaster(){
		$version = Core::retrieve('app.version');
		
		foreach(self::$Packages as $name => $package){
			array_extend($element = self::$Elements[$package['type']], splat($package['options']));
			$element['options'][$element['attribute']] = $version.'/'.$name;
			
			$el = new Element($element['options']);
			
			$assigned['package.'.$name] = $el->format();
		}
		
		$assigned['packages'] = implode($assigned);
		
		Handler::map()->assign($assigned);
	}
	
	public static function compress(){
		$package = self::$Packages[self::$Package];
		$compress = self::checkGzipCompress();
		$debug = Core::retrieve('debug');
		
		if($compress)
			Handler::setHeader(array(
				'Vary' => 'Accept-Encoding',
				'Content-Encoding' => self::$encoding,
			));
		
		$c = Cache::getInstance();
		if(!$debug){
			$expiration = Core::retrieve('expiration');
			Handler::setHeader(array(
				'Expires' => date('r', time()+$expiration),
				'Cache-Control' => 'public, max-age='.$expiration,
			));
			
			$output = $c->retrieve('Compressed', self::$Package.($compress ? '1' : ''), 'file', false);
			if($output) return $output;
		}
		
		foreach($package['files'] as $file)
			$source[] = file_get_contents(realpath(self::$Directories[$package['type']].'/'.$file.'.'.$package['type']));
		
		$compressor = new JavaScriptPacker(implode($source), 'None', false);
			
		$content = $compressor->pack();
		$c->store('Compressed', self::$Package, $content, 'file', false);
		
		$gzipcontent = gzencode($content, 9, FORCE_GZIP);
		$c->store('Compressed', self::$Package.'1', $gzipcontent, 'file', false);
		
		return $compress && $gzipcontent ? $gzipcontent : $content;
	}
	
	private static function checkGzipCompress(){
		if(self::$compress===null){
			self::$compress = false;
			
			$uagent = $_SERVER['HTTP_USER_AGENT'];
			
			
			$encodings = array();
			if($_SERVER['HTTP_ACCEPT_ENCODING'])
				$encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));
		
			if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings)) && !ini_get('zlib.output_compression'))
				self::$encoding = (in_array('x-gzip', $encodings) ? 'x-' : '').'gzip';
			
			if(self::$encoding && !preg_match('/msie (4|5|6).*[0-9]*b*;/i', $uagent))
				self::$compress = true;
			elseif(self::$encoding && strpos($uagent, 'SV1')!==false)
				self::$compress = true;
		}
		
		return self::$compress && self::$encoding;
	}
	
}