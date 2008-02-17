<?php
class Cache {
	public static $_PREFIX = 'framework_', //only for eaccelerator to have two versions parallel (dev and online)
		$eAccelerator = false;
	private static $instance,
		$cache = array();
	
	private function __construct(){
		if(function_exists('eaccelerator_get'))
			self::$eAccelerator = true;
	}
	private function __clone(){}
	
	public static function getInstance(){
		if(!self::$instance)
			self::$instance = new Cache();
		
		return self::$instance;
	}
	
	public function retrieve($key, $id, $expire = null){
		if(self::$cache[$key.'/'.$id])
			return self::$cache[$key.'/'.$id];
		
		if(self::$eAccelerator && $expire!=-1){
			$content = eaccelerator_get(self::$_PREFIX.$key.'/'.$id);
		}else{
			$file = config::$_CACHEDIR.$key.'/'.$id.'.txt';
			if(!file_exists($file)) return null;
			
			$content = explode('|', file_get_contents($file), 2);
			
			if($content[0]<time() && $content[0]!=-1) return null;
		}
		
		self::$cache[$key.'/'.$id] = json_decode($content, true);
		return self::$cache[$key.'/'.$id];
	}
	
	public function store($key, $id, $content, $expire = 3600){
		if(!$content) return;
		self::$cache[$key.'/'.$id] = $content = Util::cleanWhitespaces($content);
		$content = json_encode($content);
		if(self::$eAccelerator && $expire!=-1){
			eaccelerator_put(self::$_PREFIX.$key.'/'.$id, $content, $expire);
		}else{
			$file = config::$_CACHEDIR.$key.'/'.$id.'.txt';
			if(!file_exists($file)){
				touch($file); 
				chmod($file, 0777);
			}
			file_put_contents($file, ($expire==-1 ? -1 : time()+$expire).'|'.$content);
		}
	}
	
	public function erase($key, $id, $expire = null){
		if(self::$eAccelerator && !$expire){
			$this->removeCache($key.'/'.$id);
		}else{
			unset(self::$cache[$key.'/'.$id]);
			$this->removeFileCache($key.'/'.$id.'.txt', $expire);
		}
	}
	
	public function eraseByStart($key, $id){
		if(self::$eAccelerator){
			$keys = eaccelerator_list_keys();
			foreach($keys as $val)
				if(Util::startsWith($val['name'], ':'.self::$_PREFIX.$key.'/'.$id))
					$this->removeCache($key.'/'.substr($val['name'], strrpos($val['name'], '/')+1));
		}else{
			$this->removeFileCache($key.'/'.$id.'*.txt');
		}
	}
	
	public function eraseAll(){
		if(self::$eAccelerator){
			$keys = eaccelerator_list_keys();
			foreach($keys as $val)
				if(Util::startsWith($val['name'], ':'.self::$_PREFIX))
					$this->removeCache(substr($val['name'], strrpos($val['name'], self::$_PREFIX)+strlen(self::$_PREFIX)));
		}else{
			$this->removeFileCache('*/*.txt');
		}
	}
	
	private function removeCache($cache){
		unset(self::$cache[$cache]);
		$cache = self::$_PREFIX.$cache;
		eaccelerator_lock($cache);
		eaccelerator_rm($cache);
		eaccelerator_unlock($cache);
	}
	
	private function removeFileCache($key, $force = false){
		$files = glob(config::$_CACHEDIR.$key);
		if(!is_array($files))
			return;
		
		$error_reporting = ini_set('error_reporting', 0);
		foreach($files as $file){
			$content = explode('|', file_get_contents($file), 2);
			if($content[0]==-1 && $force!='force')
				continue;
			unlink($file);
		}
		ini_set('error_reporting', $error_reporting);
	}
}
?>