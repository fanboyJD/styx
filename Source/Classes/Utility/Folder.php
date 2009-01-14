<?php
/**
 * Styx::Folder - Provides simple directory methods
 *
 * @package Styx
 * @subpackage Utility
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Folder {
	
	/**
	 * Recursively creates a folder
	 *
	 * @param string $path
	 * @param int $mode
	 * @return bool
	 */
	public static function mkdir($path, $mode = 0777){
		return is_dir($path) || (self::mkdir(dirname($path), $mode) && self::rmkdir($path, $mode));
	}
	
	/**
	 * Helper method for Folder::mkdir()
	 *
	 * @param string $path
	 * @param int $mode
	 * @return bool
	 */
	private static function rmkdir($path, $mode = 0777){
		try{
			$old = umask(0);
			$res = mkdir($path, $mode);
			umask($old);
		}catch(Exception $e){}
		
		return $res;
	}
	
}