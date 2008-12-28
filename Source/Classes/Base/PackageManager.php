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
		$compress = null,
		$encoding = null;
	
	public static function add($name, $options = array(
		'type' => '',
		'readable' => false,
		'files' => array(),
		'options' => array(),
		'require' => array(),
	)){
		static $version;
		
		if(!$version) $version = Core::retrieve('app.version');
		
		Hash::splat($options['files']);
		Hash::splat($options['require']);
		
		Route::connect($version.'/'.$name, array(
			'package' => $name,
			'equals' => true,
			'contenttype' => self::$Elements[$options['type']]['class'],
			'preventDefault' => true, /* This is not necessary but a prevention :) */
		));
		
		self::$Packages[$name] = $options;
	}
	
	public static function assignPackages(){
		if(!count(self::$Packages)) return;
		
		$version = Core::retrieve('app.version');
		$assigned = array();
		
		foreach(self::$Packages as $name => $package){
			if(!self::checkRequired($package['require']))
				continue;
			
			$element = self::$Elements[$package['type']];
			Hash::extend($element['options'], Hash::splat($package['options']));
			$element['options'][$element['attribute']] = $version.'/'.$name;
			
			$el = new Element($element['options']);
			
			$assigned['package.'.$name] = $el->format();
		}
		
		Page::getInstance()->assign($assigned);
	}
	
	public static function showPackage($name){
		if(empty(self::$Packages[$name])) die;
		
		Page::getInstance()->assign(array('package' => $name))->show();
		die;
	}
	
	public static function compress($content){
		if(empty($content['package']) || empty(self::$Packages[$content['package']]))
			return '';
		
		$name = $content['package'];
		$package = self::$Packages[$content['package']];
		if(!self::checkRequired($package['require']))
			return '';
		
		unset($content);
		$compress = self::checkGzipCompress();
		$Configuration = Core::fetch('debug', 'app.path', 'expiration');
		
		if($compress)
			Response::setHeader(array(
				'Vary' => 'Accept-Encoding',
				'Content-Encoding' => self::$encoding,
			));
		
		$c = Cache::getInstance();
		
		if(!empty($Configuration['debug'])){
			$time = 0;
			
			/* We check here if the files have been modified */
			foreach($package['files'] as $file)
				$time = max($time, filemtime(realpath($Configuration['app.path'].'/'.self::$Elements[$package['type']]['directory'].'/'.$file.'.'.$package['type'])));
			
			if($time<$c->retrieve('CompressedTime/'.$name))
				$Configuration['debug'] = false;
		}else{
			Response::setHeader(array(
				'Expires' => date('r', time()+$Configuration['expiration']),
				'Cache-Control' => 'public, max-age='.$Configuration['expiration'],
			));
		}
		
		if(empty($Configuration['debug'])){
			$output = $c->retrieve('Compressed/'.$name.($compress ? '1' : ''));
			if($output) return $output;
		}
		
		foreach($package['files'] as $file)
			$source[] = file_get_contents(realpath($Configuration['app.path'].'/'.self::$Elements[$package['type']]['directory'].'/'.$file.'.'.$package['type']));
		
		$content = implode($source);
		
		if(empty($package['readable'])){
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
		
		$options = array(
			'encode' => false,
			'ttl' => 0,
		);
		
		$c->store('Compressed/'.$name, $content, $options);
		$c->store('Compressed/'.$name.'1', $gzipcontent, $options);
		
		$c->store('CompressedTime/'.$name, time(), $options);
		
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
			
			if(!empty($require['login']) && !User::retrieve())
				return false;
			
			if(!empty($require['browser']) && ($require['browser']!=$client['browser'] || (!$require['version'] || !in_array($client['version'], Hash::splat($require['version'])))))
				return false;
		}
		
		return true;
	}
	
}