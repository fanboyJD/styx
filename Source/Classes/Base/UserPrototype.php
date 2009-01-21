<?php
/*
 * Styx::User - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles user login and user data
 *
 */

class UserPrototype {
	
	private static $Configuration = array(),
		$rights = array(),
		$user = null;
	
	public static function initialize(){
		self::$Configuration = array_merge(Core::retrieve('user'), Core::fetch('prefix', 'identifier.internal', 'secure'));
	}
	
	public static function store($user){
		self::setRights($user && !empty(self::$Configuration['rights']) && !empty($user[self::$Configuration['rights']]) ? $user[self::$Configuration['rights']] : null);
		
		return self::$user = (is_array($user) ? $user : false);
	}
	
	public static function retrieve(){
		return is_array(self::$user) ? self::$user : false;
	}
	
	public static function get($name){
		return is_array(self::$user) && self::$user[$name] ? self::$user[$name] : null;
	}
	
	private static function getLoginData(){
		$data = array();
		
		if(self::$Configuration['type']=='cookie'){
			$cookie = Request::retrieve('cookie');
			
			$data = json_decode(!empty($cookie[self::$Configuration['prefix']]) ? (string)$cookie[self::$Configuration['prefix']] : null, true);
		}
		
		return Hash::length($data)==3 ? $data : false;
	}
	
	public static function handle($cache = true){
		$data = self::getLoginData();
		
		if(!$data) return;
		
		foreach(self::$Configuration['fields'] as $v){
			$fields[$v] = $data[$v];
			$fields[] = 'AND';
		}
		array_pop($fields);
		
		$user = Database::select(self::$Configuration['table'], $cache)->where($fields)->fetch();
		
		if($user[self::$Configuration['identifier.internal']]) return self::store($user);
		
		self::logout();
	}
	
	public static function login($user){
		$rand = self::$Configuration['secure'].mt_rand(0, 100000);
		$user[self::$Configuration['session']] = sha1($rand.uniqid($rand, true));
		
		Database::update(self::$Configuration['table'])->set(array(
			self::$Configuration['session'] => $user[self::$Configuration['session']],
		))->where(array(
			self::$Configuration['identifier.internal'] => $user[self::$Configuration['identifier.internal']],
		))->query();
		
		if(self::$Configuration['type']=='cookie'){
			foreach(self::$Configuration['fields'] as $v)
				$json[$v] = $user[$v];
			
			Response::setCookie(self::$Configuration['prefix'], json_encode($json));
		}
		
		return self::handle(false);
	}
	
	public static function logout(){
		if(self::$Configuration['type']=='cookie')
			Response::removeCookie(self::$Configuration['prefix']);
		
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
	
	public static function getPassword($password){
		return sha1(Core::retrieve('secure').$password);
	}
	
	public static function setRights(){
		$args = Hash::args(func_get_args());
		
		if(Hash::length($args)==1 && !is_array($args[0])) $args = json_decode($args[0], true);
		
		self::$rights = Hash::flatten(Hash::splat($args));
	}
	
	public static function removeRight(){
		$args = Hash::args(func_get_args());
		
		foreach($args as $arg)
			foreach(self::$rights as $k => $right)
				if($arg==$right || String::starts($right, $arg.'.'))
					unset(self::$rights[$k]);
	}
	
	public static function addRight($right){
		self::$rights[] = $right;
	}
	
	public static function hasRight(){
		if(!is_array(self::$user))
			return false;
		
		$args = Hash::args(func_get_args());
		
		$list = array();
		foreach($args as $k => $arg)
			foreach(explode('.', $arg) as $a)
				$list[] = $a;
		
		return !!self::checkRight($list);
	}
	
	private static function checkRight($rights){
		if(!is_array($rights)) return false;
		
		foreach($rights as $right){
			$prefix[] = $right;
			$check = implode('.', $prefix);
			
			if(in_array($check, self::$rights))
				return $check;
		}
		
		return false;
	}
	
}