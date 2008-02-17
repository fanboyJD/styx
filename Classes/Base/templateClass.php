<?php
class Template {
	public $root = 'tpl/',
		$tpl = array(),
		$tmp = null;
	private static $instance;
	private function __construct(){}
	private function __clone(){}
	public static function getInstance($file = null){
		if(!self::$instance)
			self::$instance = new template();
		if($file)
			self::$instance->addFile($file);
		return self::$instance;
	}
	private function flush($file){
		$this->tpl[$file] = array();
		$this->tmp = null;
	}
	public function addFile($file){
		$filename = realpath($this->root.$file.'.tpl');
		if(file_exists($filename))
			$this->tmp = $file;
		return $this;
	}
	public function getFile($file){
		/* @var $c cache */
		$c = cache::getInstance();
		
		if(!config::$_TESTSERVER)
			$content = $c->get('tpl', $file);
		if(!$content){
			$content = file_get_contents(realpath($this->root.$file.'.tpl'));
			$c->set('tpl', $file, $content, ONE_WEEK);
		}
		return $content;
	}
	public function parse($return = 0){
		if(!$this->tmp)
			return;
		$file = $this->tmp;
		
		if(!$this->tpl[$file])
			$this->tpl[$file] = $this->tpl['all'];
		$tpl = $this->getFile($file);
		preg_match_all('/\{([A-z0-9\-_]+?)\}/i', $tpl, $vars);
		foreach($vars[0] as $key => $val){
			$rep[0][] = $val;
			$rep[1][] = $this->tpl[$file][$vars[1][$key]];
		}
		$rep[0][] = "\t";
		$rep[1][] = '';
		$tpl = str_replace($rep[0], $rep[1], $tpl);
		$this->flush($file);
		if($return)
			return $tpl;
		
		echo $tpl;
		flush();
	}
	public function assign($array, $file = 'all'){
		if(!is_array($array))
			return $this;
		if($this->tmp)
			$file = $this->tmp;
		if(!$this->tpl[$file])
			$this->tpl[$file] = array();
		$this->tpl[$file] = array_merge($this->tpl[$file], $array);
		return $this;
	}
}
?>