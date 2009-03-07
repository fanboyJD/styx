<?php
/*
 * Styx::User - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles user login and user data
 *
 */

class UserPrototype {
	
	protected static $Configuration = array(),
		$rights = array(),
		$user = null;
	
	public static function initialize(){
		User::$Configuration = array_merge(Core::retrieve('user'), Core::fetch('prefix', 'identifier.internal', 'secure'));
	}
	
	public static function store($user){
		User::setRights($user && !empty(User::$Configuration['rights']) && !empty($user[User::$Configuration['rights']]) ? $user[User::$Configuration['rights']] : null);
		
		return User::$user = (is_array($user) ? $user : false);
	}
	
	public static function retrieve(){
		return User::$user ? User::$user : false;
	}
	
	public static function get($name){
		return User::$user && !empty(User::$user[$name]) ? User::$user[$name] : null;
	}
	
	private static function getLoginData(){
		$data = array();
		
		if(User::$Configuration['type']=='cookie'){
			$cookie = Request::retrieve('cookie');
			
			$data = json_decode(!empty($cookie[User::$Configuration['prefix']]) ? (string)$cookie[User::$Configuration['prefix']] : null, true);
		}
		
		return Hash::length($data)==3 ? $data : false;
	}
	
	public static function handle($cache = true){
		$data = User::getLoginData();
		
		if(!$data) return;
		
		foreach(User::$Configuration['fields'] as $v){
			$fields[$v] = $data[$v];
			$fields[] = 'AND';
		}
		array_pop($fields);
		
		$user = Database::select(User::$Configuration['table'], $cache)->where($fields)->fetch();
		
		if($user[User::$Configuration['identifier.internal']]) return User::store($user);
		
		User::logout();
	}
	
	public static function login($user){
		$rand = User::$Configuration['secure'].mt_rand(0, 100000);
		$user->updateSession(sha1($rand.uniqid($rand, true)));
		
		if(User::$Configuration['type']=='cookie'){
			foreach(User::$Configuration['fields'] as $v)
				$json[$v] = $user[$v];
			
			Response::setCookie(User::$Configuration['prefix'], json_encode($json));
		}
		
		return User::handle(false);
	}
	
	public static function logout(){
		if(User::$Configuration['type']=='cookie')
			Response::removeCookie(User::$Configuration['prefix']);
		
		User::store(false);
	}
	
	public static function checkSession($sid){
		$user = User::retrieve();
		$data = User::getLoginData();
		
		if(!$user || $user[User::$Configuration['session']]!=$sid || $data[User::$Configuration['session']]!=$sid)
			return false;

		foreach(User::$Configuration['fields'] as $v)
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
		
		User::$rights = Hash::flatten(Hash::splat($args));
	}
	
	public static function removeRight(){
		$args = Hash::args(func_get_args());
		
		foreach($args as $arg)
			foreach(User::$rights as $k => $right)
				if($arg==$right || String::starts($right, $arg.'.'))
					unset(User::$rights[$k]);
	}
	
	public static function addRight($right){
		User::$rights[] = $right;
	}
	
	public static function hasRight(){
		if(!is_array(User::$user))
			return false;
		
		$args = Hash::args(func_get_args());
		
		$list = array();
		foreach($args as $k => $arg)
			foreach(explode('.', $arg) as $a)
				$list[] = $a;
		
		return !!User::checkRight($list);
	}
	
	private static function checkRight($rights){
		if(!is_array($rights)) return false;
		
		foreach($rights as $right){
			$prefix[] = $right;
			$check = implode('.', $prefix);
			
			if(in_array($check, User::$rights))
				return $check;
		}
		
		return false;
	}
	
}