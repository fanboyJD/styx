<?php
/*
 * Styx::User - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles user login and user data
 *
 */

class UserPrototype {
	
	protected static $rights = array();
	protected static $user = null;
	
	protected static function onGetLoginData(){
		$cookie = Request::retrieve('cookie');
		return empty($cookie[$prefix = Core::retrieve('prefix')]) ? null : json_decode((string)$cookie[$prefix], true);
	}
	
	protected static function onLogin($data){
		Response::setCookie(Core::retrieve('prefix'), json_encode($data));
	}
	
	protected static function onLogout(){
		Response::removeCookie(Core::retrieve('prefix'));
	}
	
	public static function store($user){
		User::setRights($user ? $user->getRights() : null);
		
		return User::$user = pick($user, false);
	}
	
	public static function retrieve(){
		return User::$user ? User::$user : false;
	}
	
	public static function get($name){
		return User::$user ? User::$user[$name] : null;
	}
	
	protected static function getLoginData(){
		$data = (array)User::onGetLoginData();
		return count(array_intersect_key($data, array(
			'name' => true,
			'pwd' => true,
			'session' => true,
		)))==3 ? $data : false;
	}
	
	public static function handle($cache = true){
		return ($user = Model::create('User')->setCache($cache)->findByLoginData(User::getLoginData())) ? User::store($user) : User::logout();
	}
	
	public static function login($user){
		$rand = Core::retrieve('secure').mt_rand(0, 100000);
		User::onLogin($user->updateSession(sha1($rand.uniqid($rand, true)))->getLoginData());
		return User::handle(false);
	}
	
	public static function logout(){
		User::onLogout();
		User::store(false);
	}
	
	public static function checkSession($sid){
		$user = User::retrieve();
		$data = User::getLoginData();
		
		if(!$user || empty($data['session']) || !in_array($sid, array($user->getSession(), $data['session'])))
			return false;
		
		foreach($user->getLoginData() as $key => $value)
			if(!$value || empty($data[$key]) || $data[$key]!=$value)
				return false;
		
		return true;
	}
	
	public static function getPassword($password){
		return sha1(Core::retrieve('secure').$password);
	}
	
	public static function setRights(){
		$args = Hash::args(func_get_args());
		
		if(Hash::length($args)==1 && is_string($args[0])) $args = json_decode($args[0], true);
		
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
		if(!User::$user) return false;
		
		$list = array();
		foreach(Hash::args(func_get_args()) as $k => $arg)
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