<?php
class Cache {
	private $prefix = 'framework_',
		$root = './Cache/',
		$cache = array();
	
	private static $instance,
		$engine = false;
	
	private function __construct($options = array()){
		if((!$options['engine'] || $options['engine']=='eaccelerator') && function_exists('eaccelerator_get'))
			self::$engine = array(
				'type' => 'eaccelerator',
			);
		
		if($options['prefix'])
			$this->prefix = $options['prefix'];
		
		if($options['root'])
			$this->root = realpath($options['root']);
		else
			$this->root = Env::retrieve('basePath').$this->root;
	}
	
	private function __clone(){}
	
	public static function getInstance($options = null){
		if(!self::$instance)
			self::$instance = new Cache($options);
		
		return self::$instance;
	}
	
	public function matchEngine($match){
		return self::$engine['type']==$match;
	}
	
	public function retrieve($key, $id, $expire = null){
		if($this->cache[$key.'/'.$id])
			return $this->cache[$key.'/'.$id];
		
		if(self::matchEngine('eaccelerator') && $expire!=-1){
			$content = eaccelerator_get($this->prefix.$key.'/'.$id);
		}else{
			$file = $this->root.$this->prefix.'/'.$key.'/'.$id.'.txt';
			if(!file_exists($file)) return null;
			
			$content = explode('|', file_get_contents($file), 2);
			
			if($content[0]<time() && $content[0]!=-1) return null;
		}
		
		$this->cache[$key.'/'.$id] = json_decode($content, true);
		return $this->cache[$key.'/'.$id];
	}
	
	public function store($key, $id, $content, $expire = 3600){
		if(!$content) return;
		$this->cache[$key.'/'.$id] = $content = Util::cleanWhitespaces($content);
		$content = json_encode($content);
		if(self::matchEngine('eaccelerator') && $expire!=-1){
			eaccelerator_put($this->prefix.$key.'/'.$id, $content, $expire);
		}else{
			$file = $this->root.$this->prefix.'/'.$key.'/'.$id.'.txt';
			if(!file_exists($file)){
				try{
					$dir = dirname($file);
					if(!is_dir($dir)){
						if(!is_dir($this->root.$this->prefix))
							mkdir($this->root.$this->prefix, 0777);
						
						mkdir(dirname($file), 0777);
					}
					touch($file); 
					chmod($file, 0777);
				}catch(Exception $e){}
			}
			file_put_contents($file, ($expire==-1 ? -1 : time()+$expire).'|'.$content);
		}
	}
	
	public function erase($key, $id, $expire = null){
		if(self::matchEngine('eaccelerator') && !$expire){
			$this->removeCache($key.'/'.$id);
		}else{
			unset($this->cache[$key.'/'.$id]);
			$this->removeFileCache($key.'/'.$id.'.txt', $expire);
		}
	}
	
	public function eraseByStart($key, $id){
		if(self::matchEngine('eaccelerator')){
			$keys = eaccelerator_list_keys();
			foreach($keys as $val)
				if(Util::startsWith($val['name'], ':'.$this->prefix.$key.'/'.$id))
					$this->removeCache($key.'/'.substr($val['name'], strrpos($val['name'], '/')+1));
		}else{
			$this->removeFileCache($key.'/'.$id.'*.txt');
		}
	}
	
	public function eraseAll(){
		if(self::matchEngine('eaccelerator')){
			$keys = eaccelerator_list_keys();
			foreach($keys as $val)
				if(Util::startsWith($val['name'], ':'.$this->prefix))
					$this->removeCache(substr($val['name'], strrpos($val['name'], $this->prefix)+strlen($this->prefix)));
		}else{
			$this->removeFileCache('*/*.txt');
		}
	}
	
	private function removeCache($cache){
		unset($this->cache[$cache]);
		$cache = $this->prefix.$cache;
		eaccelerator_lock($cache);
		eaccelerator_rm($cache);
		eaccelerator_unlock($cache);
	}
	
	private function removeFileCache($key, $force = false){
		$files = glob($this->root.$this->prefix.'/'.$key);
		if(!is_array($files))
			return;
		
		try{
			foreach($files as $file){
				$content = explode('|', file_get_contents($file), 2);
				if($content[0]==-1 && $force!='force')
					continue;
				unlink($file);
			}
		}catch(Exception $e){}
	}
}
?>