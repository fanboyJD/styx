<?php
class Mime {
	
	private static $mime;
	
	public static function retrieve($file){
		if(!self::$mime)
			self::$mime = parse_ini_file(Core::retrieve('path').'Config/mimeTypes.ini');
		
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		
		return !$ext || !self::$mime[$ext] ? 'text/plain' : self::$mime[$ext];
	}
	
}