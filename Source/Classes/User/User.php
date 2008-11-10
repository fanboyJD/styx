<?php
/*
 * Styx::User - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles user login and user data
 *
 */

class User {
	
	/**
	 * @var Rights
	 */
	private static $Rights;
	
	private static $Configuration = array(),
		$rights = array(),
		$user = null;
	
	public static function initialize(){
		self::$Configuration = Core::retrieve('user');
		
		self::$Rights = new Rights();
		
		self::handlelogin();
	}
	
	public static function store($user){
		self::$user = false;
		
		self::$Rights->setRights($user && self::$Configuration['rights'] ? $user[self::$Configuration['rights']] : null);
		
		if(is_array($user)) self::$user = $user;
		
		return self::$user;
	}
	
	public static function retrieve(){
		return is_array(self::$user) ? self::$user : false;
	}
	
	public static function get($name){
		return is_array(self::$user) && self::$user[$name] ? self::$user[$name] : null;
	}
	
	private static function getLoginData(){
		if(self::$Configuration['type']=='cookie'){
			$prefix = Core::retrieve('prefix');
			$cookie = Request::getInstance()->retrieve('cookie');
			
			$data = json_decode(!empty($cookie[$prefix]) ? (string)$cookie[$prefix] : null, true);
		}
		
		return Hash::length($data)==3 ? $data : false;
	}
	
	public static function handlelogin($cache = true){
		$data = self::getLoginData();
		
		if(!$data) return;
		
		$id = Core::retrieve('identifier.internal');
		
		foreach(self::$Configuration['fields'] as $v){
			$fields[$v] = $data[$v];
			$fields[] = 'AND';
		}
		array_pop($fields);
		
		$user = db::select(self::$Configuration['table'], $cache)->where($fields)->fetch();
		
		if($user[$id]) return self::store(Cache::getInstance()->store('User', 'userdata_'.$user[self::$Configuration['session']], $user, ONE_DAY));
		
		self::logout();
	}
	
	public static function login($user){
		if($user[self::$Configuration['session']]) Cache::getInstance()->erase('User', 'userdata_'.$user[self::$Configuration['session']]);
		
		$rand = Core::retrieve('secure').mt_rand(0, 100000);
		$user[self::$Configuration['session']] = sha1($rand.uniqid($rand, true));
		
		$id = Core::retrieve('identifier.internal');
		db::update(self::$Configuration['table'])->set(array(
			self::$Configuration['session'] => $user[self::$Configuration['session']],
		))->where(array(
			$id => $user[$id],
		))->query();
		
		if(self::$Configuration['type']=='cookie'){
			foreach(self::$Configuration['fields'] as $v)
				$json[$v] = $user[$v];
			
			Page::setCookie(Core::retrieve('prefix'), json_encode($json));
		}
		
		return self::handlelogin(false);
	}
	
	public static function logout(){
		Cache::getInstance()->erase('User', 'userdata_'.User::get(self::$Configuration['session']));
		
		if(self::$Configuration['type']=='cookie')
			Page::removeCookie(Core::retrieve('prefix'));
		
		self::store(false);
	}
	
	public static function checkSession($sid){
		$user = self::retrieve();
		$data = self::getLoginData();
		
		if(!$user || $user[self::$Configuration['session']]!=$sid || $data[self::$Configuration['session']]!=$sid)
			return false;

		foreach(self::$Configuration['fields'] as $v)
			if(!$user[$v] || !$data[$v] || $user[$v]!=$data[$v])
				return false;
		
		return true;
	}
	
	public static function hasRight(){
		$args = Hash::args(func_get_args());
		
		return self::retrieve() && self::$Rights->hasRight($args);
	}
	
}