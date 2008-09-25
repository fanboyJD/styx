<?php

class Folder {
	
	public static function mkdir($path, $mode = 0777){
		return is_dir($path) || (self::mkdir(dirname($path), $mode) && self::rmkdir($path, $mode));
	}
	
	private static function rmkdir($path, $mode = 0777){
		try{
			$old = umask(0);
			$res = mkdir($path, $mode);
			umask($old);
		}catch(Exception $e){}
		
		return $res;
	}
	
}