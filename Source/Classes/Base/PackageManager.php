<?php
/*
 * Styx::PackageManager - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Manages, compresses and streams packages (CSS/JavaScript/...) for the application to the client
 *
 */

class PackageManager {
	
	private static $Elements = array(
			'js' => array(
				'class' => 'JavaScript',
				'directory' => 'JavaScript',
				'attribute' => 'src',
				'options' => array(
					':tag' => 'script',
					':unknown' => true,
					'type' => 'text/javascript',
				),
			),
			'css' => array(
				'class' => 'CSS',
				'directory' => 'Css',
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
		'require' => array(),
	)){
		Hash::splat($options['files']);
		Hash::splat($options['require']);
		
		self::$Packages[$name] = $options;
	}
	
	private static function has($name){
		return is_array(self::$Packages[$name]);
	}
	
	public static function setPackage($name){
		if(self::has($name)){
			$class = self::$Elements[self::$Packages[$name]['type']]['class'];
			Page::allow($class);
			$class = $class.'Content';
			Page::setContentType(new $class);
			
			self::$Package = $name;
			return true;
		}
		
		return false;
	}
	
	public static function getType(){
		return self::$Packages[self::$Package]['type'];
	}
	
	public static function assignToPage(){
		if(!count(self::$Packages)) return;
		
		$version = Core::retrieve('app.version');
		
		foreach(self::$Packages as $name => $package){
			if(!self::checkRequired($package['require']))
				continue;
			
			$element = self::$Elements[$package['type']];
			Hash::extend($element['options'], Hash::splat($package['options']));
			$element['options'][$element['attribute']] = $version.'/'.$name;
			
			$el = new Element($element['options']);
			
			$assigned['package.'.$name] = $el->format();
		}
		
		$assigned['packages'] = implode($assigned);
		
		Page::getInstance()->assign($assigned);
	}
	
	public static function compress(){
		$package = self::$Packages[self::$Package];
		if(!self::checkRequired($package['require']))
			return '';
		
		$compress = self::checkGzipCompress();
		$debug = Core::retrieve('debug');
		$path = Core::retrieve('app.path');
		
		if($compress)
			Page::setHeader(array(
				'Vary' => 'Accept-Encoding',
				'Content-Encoding' => self::$encoding,
			));
		
		$c = Cache::getInstance();
		
		if($debug){
			/* We check here if the files have been modified */
			foreach($package['files'] as $file)
				$time = max($time, filemtime(realpath($path.'/'.self::$Elements[$package['type']]['directory'].'/'.$file.'.'.$package['type'])));
			
			if($time<$c->retrieve('CompressedTime', self::$Package, 'file'))
				$debug = false;
		}else{
			$expiration = Core::retrieve('expiration');
			Page::setHeader(array(
				'Expires' => date('r', time()+$expiration),
				'Cache-Control' => 'public, max-age='.$expiration,
			));
		}
		
		if(!$debug){
			$output = $c->retrieve('Compressed', self::$Package.($compress ? '1' : ''), 'file', false);
			if($output) return $output;
		}
		
		foreach($package['files'] as $file)
			$source[] = file_get_contents(realpath($path.'/'.self::$Elements[$package['type']]['directory'].'/'.$file.'.'.$package['type']));
		
		$content = implode($source);
		
		if(!$package['keepReadable']){
			if($package['type']=='js'){
				$compressor = new JavaScriptPacker($content, 'None', false);
				$content = $compressor->pack();
			}else{
				$content = str_replace(
					array('{ ',' }', '; ', ';}', ': ', ', '),
					array('{', '}', ';', '}', ':', ','),
					Data::clean(preg_replace('/\s{2,}/', '', preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content)), true)
				);
			}
		}
		
		$gzipcontent = gzencode($content, 9, FORCE_GZIP);
		
		$c->store('Compressed', self::$Package, $content, 'file', false);
		$c->store('Compressed', self::$Package.'1', $gzipcontent, 'file', false);
		
		$c->store('CompressedTime', self::$Package, time(), 'file');
		
		return $compress ? $gzipcontent : $content;
	}
	
	private static function checkGzipCompress(){
		if(self::$compress===null){
			self::$compress = false;
			
			$client = Request::getClient();
			
			$encodings = array();
			if($_SERVER['HTTP_ACCEPT_ENCODING'])
				$encodings = explode(',', strtolower(preg_replace('/\s+/', '', $_SERVER['HTTP_ACCEPT_ENCODING'])));
		
			if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings)) && !ini_get('zlib.output_compression'))
				self::$encoding = (in_array('x-gzip', $encodings) ? 'x-' : '').'gzip';
			
			if(self::$encoding && ($client['browser']!='ie' || $client['version']>6 || $client['features']['servicePack']))
				self::$compress = true;
		}
		
		return self::$compress && self::$encoding;
	}
	
	private static function checkRequired($require){
		if(count($require)){
			$client = Request::getClient();
			
			if($require['login'] && !User::retrieve())
				return false;
			
			if($require['browser'] && ($require['browser']!=$client['browser'] || (!$require['version'] || !in_array($client['version'], Hash::splat($require['version'])))))
				return false;
		}
		
		return true;
	}
	
}