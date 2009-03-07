<?php
/**
 * Styx::PackageManager - Manages, compresses and streams packages (CSS/JavaScript/...) to the client
 *
 * @package Styx
 * @subpackage Base
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class PackageManager {
	
	/**
	 * Holds the configuration for JavaScript and CSS Packages
	 *
	 * @var array
	 */
	private static $Elements = array(
			'js' => array(
				'class' => 'JavaScript',
				'directory' => 'JavaScript',
				'tag' => '<script src="{package}" type="{type}"></script>',
				'options' => array(
					'type' => 'text/javascript',
				),
			),
			'css' => array(
				'class' => 'CSS',
				'directory' => 'Css',
				'tag' => '<link rel="stylesheet" media="{media}" type="{type}" href="{package}" />',
				'options' => array(
					'media' => 'all',
					'type' => 'text/css',
				),
			),
		);
	/**
	 * Holds a list of the defined packages
	 *
	 * @var array
	 */
	private static $Packages = array();
	/**
	 * The encoding to be used
	 *
	 * @var string
	 */
	private static $encoding;
	
	/**
	 * Adds a package with {@see $name} and the given {@see $options}
	 *
	 * @param string $name
	 * @param array $options
	 */
	public static function add($name, $options = null){
		static $Configuration;
		
		if(!$Configuration) $Configuration = Core::fetch('app.version');
		
		$default = array_merge(array(
			'type' => '',
			'readable' => false,
			'files' => array(),
			'options' => array(),
			'require' => array(),
			'replaces' => array(),
		), $options);
		
		Route::connect($Configuration['app.version'].'/'.$name, array(
			'package' => $name,
			'equals' => true,
			'contenttype' => self::$Elements[$options['type']]['class'],
			'preventDefault' => true, /* This is not necessary but a prevention :) */
		));
		
		self::$Packages[$name] = $default;
	}
	
	/**
	 * Assigns the html-tags of the defined packages to the Page-Template
	 *
	 */
	public static function assignPackages(){
		if(!count(self::$Packages)) return;
		
		static $Configuration;
		
		if(!$Configuration) $Configuration = Core::fetch('app.version');
		
		$assigned = array();
		
		foreach(self::$Packages as $name => $package){
			if(!self::checkRequired($package['require']))
				continue;
			
			$element = self::$Elements[$package['type']];
			Hash::extend($element['options'], $package['options']);
			
			$options = array();
			foreach($element['options'] as $k => $option)
				$options[] = '{'.$k.'}';
			
			$options[] = '{package}';
			$element['options'][] = $Configuration['app.version'].'/'.$name;
			
			$assigned['package.'.$name] = str_replace($options, $element['options'], $element['tag']);
		}
		
		Page::getInstance()->assign($assigned);
	}
	
	/**
	 * Outputs the given package
	 *
	 * @param string $name
	 */
	public static function showPackage($name){
		if(empty(self::$Packages[$name])) die;
		
		Page::getInstance()->assign(array('package' => $name))->show();
		die;
	}
	
	/**
	 * Compresses a package, gets called from {@see ContentType->process}
	 *
	 * @param array $content
	 * @return string
	 */
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
		
		if($Configuration['debug']){
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
		
		if(!$Configuration['debug']){
			$output = $c->retrieve('Compressed/'.$name.($compress ? '1' : ''));
			if($output) return $output;
		}
		
		foreach($package['files'] as $file)
			$source[] = file_get_contents(realpath($Configuration['app.path'].'/'.self::$Elements[$package['type']]['directory'].'/'.$file.'.'.$package['type']));
		
		$content = implode($source);
		
		if($package['replaces'])
			$content = str_replace(array_keys($package['replaces']), $package['replaces'], $content);
		
		if($onCreate = Core::fireEvent('packageCreate', array(
			'name' => $name,
			'content' => $content,
		))) $content = $onCreate;
		
		if(empty($package['readable'])){
			if($package['type']=='js'){
				$compressor = new JavaScriptPacker($content, 'None', false);
				$content = $compressor->pack();
			}else{
				$content = str_replace(
					array('{ ',' }', '; ', ';}', ': ', ', '),
					array('{', '}', ';', '}', ':', ','),
					String::clean(preg_replace('/\s{2,}/', '', preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content)), true)
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
	
	/**
	 * Checks if the client (browser) is able to retrieve gzip data (as certain IE versions don't really support it)
	 *
	 * @return bool
	 */
	private static function checkGzipCompress(){
		static $compress;
		
		if($compress===null){
			$compress = false;
			
			$client = Request::getClient();
			
			$encodings = array();
			if($_SERVER['HTTP_ACCEPT_ENCODING'])
				$encodings = explode(',', strtolower(preg_replace('/\s+/', '', $_SERVER['HTTP_ACCEPT_ENCODING'])));
		
			if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings)) && !ini_get('zlib.output_compression'))
				self::$encoding = (in_array('x-gzip', $encodings) ? 'x-' : '').'gzip';
			
			if(self::$encoding && ($client['browser']!='ie' || $client['version']>6 || $client['features']['servicePack']))
				$compress = true;
		}
		
		return $compress && self::$encoding;
	}
	
	/**
	 * Checks if the client (user, browser) matches the requirements to view the given package
	 *
	 * @param array $require
	 * @return bool
	 */
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