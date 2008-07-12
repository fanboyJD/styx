<?php
class User {
	
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
		self::handlelogin();
	}
	
	public static function store($user){
		self::$user = false;
		
		if(is_array($user)){
			self::$user = $user;
			
			if(self::$rightsfield) self::setRights(self::$user[self::$rightsfield]);
		}
		
		return self::$user;
	}
	
	public static function retrieve(){
		return is_array(self::$user) ? self::$user : false;
	}
	
	private static function getLoginData(){
		if(self::$type=='cookie'){
			$pre = Core::retrieve('user.cookie');
			foreach(self::$fields as $v){
				$content = trim($_COOKIE[$pre][$v]);
				
				if($content) $data[$v] = $content;
			}
		}
		
		return is_array($data) && sizeof($data)==3 ? $data : false;
	}
	
	public static function handlelogin($forceQuery = false){
		$data = self::getLoginData();
		
		if($data){
			$id = Core::retrieve('identifier.id');
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
		
		$id = Core::retrieve('identifier.id');
		db::update(self::$table)->set(array(
			self::$sessionfield => $user[self::$sessionfield],
		))->where(array(
			$id => $user[$id],
		))->query();
		
		if(self::$type=='cookie'){
			$pre = Core::retrieve('user.cookie');
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
			$pre = Core::retrieve('user.cookie');
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
	
	public static function setRights($rights){
		if($rights && !is_array($rights)) $rights = json_decode($rights, true);
		
		self::$rights = Hash::flatten(Hash::splat($rights));
	}
	
	private static function checkRight($rights){
		if(!is_array($rights)) return false;
		
		foreach($rights as $right){
			$prefix[] = $right;
			if(self::$rights[implode('.', $prefix)]==1)
				return true;
		}
		
		return false;
	}
	
	public static function hasRight(){
		$args = Hash::args(func_get_args());
		
		return self::retrieve() && self::checkRight($args);
	}
	
}