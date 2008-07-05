<?php
class User {
	
	private static $type = 'cookie',
		$user = null;
	
	public static function initialize(){
		self::$type = pick(Core::retrieve('user.type'), self::$type);
	}
	
	public static function store($user){
		return self::$user = is_array($user) ? $user : false;
	}
	
	public static function retrieve(){
		return self::$user;
	}
	
	private static function getLoginData(){
		if(self::$type=='cookie'){
			$pre = Core::retrieve('user.cookie');
			foreach(array('name', 'pwd', 'session') as $v){
				$content = trim($_COOKIE[$pre][$v]);
				
				if($content) $data[$v] = $content;
			}
		}
		
		return is_array($data) && sizeof($data)==3 ? $data : false;
	}
	
	public static function handlelogin($forceQuery = false){
		$data = self::getLoginData();
		
		if($data){
			if(!$forceQuery){
				$user = Cache::getInstance()->get('User', 'userdata_'.$data['session']);
				if($user && $user[Core::retrieve('identifier.id')] && $user['pwd']==$data['pwd'] && $user['session']==$data['session'] && $user['name']==$data['name'])
					return $user;
			}
			
			$user = db::select('users')->where(array(
				'pwd' => $data['pwd'],
				'AND',
				'session' => $data['session'],
				'AND',
				'name' => $data['name'],
			))->fetch();
			
			if($user[Core::retrieve('identifier.id')])
				return self::store(Cache::getInstance()->set('User', 'userdata_'.$user['session'], $user, ONE_DAY));
			
			self::logout();
		}
	}
	
	public static function login($user){
		if($user['session'])
			Cache::getInstance()->clear('User', 'userdata_'.$user['session']);
		
		mt_srand((double)microtime()*1000000);
		$rand = Core::retrieve('secure').mt_rand(0, 100000);
		
		$user['session'] = md5($rand.uniqid($rand, true));
		
		db::update('users')->set(array(
			'session' => $user['session'],
		))->where(array(
			'id' => $user['id'],
		));
		
		if(self::$type=='cookie'){
			$pre = Core::retrieve('user.cookie');
			
			foreach(array('name', 'pwd', 'session') as $v){
				$time = time()+8640000;
				setcookie($pre.'['.$v.']', $user[$v], $time, '/');
				$_COOKIE[$pre][$v] = $user[$v];
			}
		}
		
		return User::handlelogin(true);
	}
	
	public static function logout(){
		Cache::getInstance()->clear('User', 'userdata_'.$_COOKIE['bg']['session']);
		
		if(self::$type=='cookie'){
			$pre = Core::retrieve('user.cookie');
			
			foreach(array('name', 'pwd', 'session') as $v){
				$time = time()-3600;
				setcookie($pre.'['.$v.']', false, $time, '/');
				unset($_COOKIE[$pre][$v]);
			}
			
			unset($_COOKIE[$pre]);
		}
	}
	
	public static function checkSession($sid){
		$user = self::retrieve();
		$data = self::getLoginData();
		
		if(!$user) return false;
		
		if(self::$type=='cookie'){
			if($user['session']!=$sid || $data['session']!=$sid)
				return false;

			foreach(array('name', 'pwd', 'session') as $v)
				if(!$user[$v] || !$data[$v] || $user[$v]!=$data[$v])
					return false;
		}
		
		return true;
	}
	
}