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
	
	private static $type = 'cookie',
		$table = 'users',
		$fields = array('name', 'pwd'),
		$sessionfield = 'session',
		$rightsfield = null,
		$rights = array(),
		$user = null;
	
	public static function initialize(){
		foreach(array('type', 'table', 'fields', 'sessionfield', 'rightsfield') as $v)
			self::$$v = pick(Core::retrieve('user.'.$v), self::$$v);
		
		self::$fields[] = self::$sessionfield;
		
		self::$Rights = new Rights();
		
		self::handlelogin();
	}
	
	public static function store($user){
		self::$user = false;
		
		if(is_array($user)){
			self::$user = $user;
			
			if(self::$rightsfield) self::$Rights->setRights(self::$user[self::$rightsfield]);
		}
		
		return self::$user;
	}
	
	public static function retrieve(){
		return is_array(self::$user) ? self::$user : false;
	}
	
	public static function get($name){
		return is_array(self::$user) && self::$user[$name] ? self::$user[$name] : null;
	}
	
	private static function getLoginData(){
		if(self::$type=='cookie'){
			$pre = Core::retrieve('prefix');
			foreach(self::$fields as $v){
				$content = trim($_COOKIE[$pre][$v]);
				
				if($content) $data[$v] = $content;
			}
		}
		
		return Hash::length($data)==3 ? $data : false;
	}
	
	public static function handlelogin($forceQuery = false){
		$data = self::getLoginData();
		
		if($data){
			$id = Core::retrieve('identifier.internal');
			/*if(!$forceQuery){
				$user = Cache::getInstance()->retrieve('User', 'userdata_'.$data[self::$sessionfield]);
				if($user && $user[$id]){
					foreach(self::$fields as $v)
						if($user[$v]!=$data[$v])
							$forceQuery = true;
						
					if(!$forceQuery) return self::store($user);
				}
			}*/
			
			foreach(self::$fields as $v){
				$fields[$v] = $data[$v];
				$fields[] = 'AND';
			}
			array_pop($fields);
			
			$user = db::select(self::$table)->where($fields)->fetch();
			
			if($user[$id]) return self::store(Cache::getInstance()->store('User', 'userdata_'.$user[self::$sessionfield], $user, ONE_DAY));
			
			self::logout();
		}
	}
	
	public static function login($user){
		if($user[self::$sessionfield]) Cache::getInstance()->erase('User', 'userdata_'.$user[self::$sessionfield]);
		
		mt_srand((double)microtime()*1000000);
		$rand = Core::retrieve('secure').mt_rand(0, 100000);
		$user[self::$sessionfield] = md5($rand.uniqid($rand, true));
		
		$id = Core::retrieve('identifier.internal');
		db::update(self::$table)->set(array(
			self::$sessionfield => $user[self::$sessionfield],
		))->where(array(
			$id => $user[$id],
		))->query();
		
		if(self::$type=='cookie'){
			$pre = Core::retrieve('prefix');
			$time = time()+8640000;
			
			foreach(self::$fields as $v){
				setcookie($pre.'['.$v.']', $user[$v], $time, '/');
				$_COOKIE[$pre][$v] = $user[$v];
			}
		}
		
		return self::handlelogin(true);
	}
	
	public static function logout(){
		if(self::$type=='cookie'){
			$pre = Core::retrieve('prefix');
			$time = time()-3600;
			
			Cache::getInstance()->erase('User', 'userdata_'.$_COOKIE[$pre][self::$sessionfield]);
			
			foreach(self::$fields as $v){
				setcookie($pre.'['.$v.']', false, $time, '/');
				unset($_COOKIE[$pre][$v]);
			}
			
			unset($_COOKIE[$pre]);
		}
		
		self::store(false);
	}
	
	public static function checkSession($sid){
		$user = self::retrieve();
		$data = self::getLoginData();
		
		if(!$user || $user[self::$sessionfield]!=$sid || $data[self::$sessionfield]!=$sid)
			return false;

		foreach(self::$fields as $v)
			if(!$user[$v] || !$data[$v] || $user[$v]!=$data[$v])
				return false;
		
		return true;
	}
	
	public static function hasRight(){
		$args = Hash::args(func_get_args());
		
		return self::retrieve() && self::$Rights->hasRight($args);
	}
	
}