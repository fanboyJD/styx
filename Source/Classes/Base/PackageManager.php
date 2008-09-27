<?php
/*
 * Styx::PackageManager - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Manages, compresses and streams packages (CSS/JavaScript/...) for the application to the client
 *
 */

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
		$uagent = null,
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
			if(!self::checkRequired($package['require']))
				continue;
			
			Hash::extend($element = self::$Elements[$package['type']], Hash::splat($package['options']));
			$element['options'][$element['attribute']] = $version.'/'.$name;
			
			$el = new Element($element['options']);
			
			$assigned['package.'.$name] = $el->format();
		}
		
		$assigned['packages'] = implode($assigned);
		
		Handler::map()->assign($assigned);
	}
	
	public static function compress(){
		$package = self::$Packages[self::$Package];
		if(!self::checkRequired($package['require']))
			return '';
		
		$compress = self::checkGzipCompress();
		$debug = Core::retrieve('debug');
		
		if($compress)
			Handler::setHeader(array(
				'Vary' => 'Accept-Encoding',
				'Content-Encoding' => self::$encoding,
			));
		
		$c = Cache::getInstance();
		
		if($debug){
			/* We check here if the files have been modified */
			foreach($package['files'] as $file)
				$time = max($time, filemtime(realpath(self::$Directories[$package['type']].'/'.$file.'.'.$package['type'])));
			
			if($time<$c->retrieve('CompressedTime', self::$Package, 'file'))
				$debug = false;
		}else{
			$expiration = Core::retrieve('expiration');
			Handler::setHeader(array(
				'Expires' => date('r', time()+$expiration),
				'Cache-Control' => 'public, max-age='.$expiration,
			));
		}
		
		if(!$debug){
			$output = $c->retrieve('Compressed', self::$Package.($compress ? '1' : ''), 'file', false);
			if($output) return $output;
		}
		
		foreach($package['files'] as $file)
			$source[] = file_get_contents(realpath(self::$Directories[$package['type']].'/'.$file.'.'.$package['type']));
		
		$content = implode($source);
		
		if(!$package['keepReadable']){
			if($package['type']=='js'){
				$compressor = new JavaScriptPacker($content, 'None', false);
				$content = $compressor->pack();
			}else{
				// Thanks to: http://gadelkareem.com/2007/06/23/compressing-your-html-css-and-javascript-using-simple-php-code/
				$content = str_replace(';}', '}',
					preg_replace('/^\s+/', '',
						preg_replace('/[\s]*([\{\},;:])[\s]*/', '\1',
							preg_replace('#/\*.*?\*/#', '',
								preg_replace('/[\r\n\t\s]+/s', ' ',
									preg_replace('!//[^\n\r]+!', '', $content)
								)
							)
						)
					)
				);
			}
		}
		
		$gzipcontent = gzencode($content, 9, FORCE_GZIP);
		
		$c->store('Compressed', self::$Package, $content, 'file', false);
		$c->store('Compressed', self::$Package.'1', $gzipcontent, 'file', false);
		
		$c->store('CompressedTime', self::$Package, time(), 'file');
		
		return $compress && $gzipcontent ? $gzipcontent : $content;
	}
	
	private static function parseUAgent(){
		if(is_array(self::$uagent)) return self::$uagent;
		
		$uagent = $_SERVER['HTTP_USER_AGENT'];
		
		if(preg_match('/msie ([0-9]).*[0-9]*b*;/i', $uagent, $m))
			self::$uagent = array(
				'browser' => 'ie',
				'version' => $m[1][0],
			);
		else
			self::$uagent = array(
				'browser' => 'compatible',
			);
		
		if(strpos($uagent, 'SV1')!==false)
			self::$uagent['features']['servicePack'] = true;
		
		return self::$uagent;
	}
	
	private static function checkGzipCompress(){
		if(self::$compress===null){
			self::$compress = false;
			
			$uagent = self::parseUAgent();
			
			$encodings = array();
			if($_SERVER['HTTP_ACCEPT_ENCODING'])
				$encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));
		
			if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings)) && !ini_get('zlib.output_compression'))
				self::$encoding = (in_array('x-gzip', $encodings) ? 'x-' : '').'gzip';
			
			if(self::$encoding && ($uagent['browser']!='ie' || $uagent['version']>6 || $uagent['features']['servicePack']))
				self::$compress = true;
		}
		
		return self::$compress && self::$encoding;
	}
	
	private static function checkRequired($require){
		if(sizeof($require)){
			$uagent = self::parseUAgent();
			
			if($require['login'] && !User::retrieve())
				return false;
			
			if($require['browser'] && ($require['browser']!=$uagent['browser'] || (!$require['version'] || !in_array($uagent['version'], Hash::splat($require['version'])))))
				return false;
		}
		
		return true;
	}
	
}