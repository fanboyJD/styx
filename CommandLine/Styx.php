#!/usr/bin/env php
<?php

class StyxCommandLine {
	
	protected $ProjectPath;
	protected $Configuration;
	
	protected $options = array(
		'styxPath' => null,
		'use' => null,
		
		// onCreate
		'table' => null,
		'identifier.internal' => null,
		'identifier.external' => null,
		'identifier' => null,
	);
	protected $commands = array();
	protected $mainCommand = null;
	
	public function invoke($args){
		$this->ProjectPath = realpath('./').'/';
		$configuration = $this->ProjectPath.'Config/Configuration.php';
		
		if(!file_exists($configuration))
			$this->stop('No Styx-Project found in the current working directory');
		
		$this->setOptions($args);
		
		$this->readConfiguration($configuration, $this->options['use']);
		$this->invokeCommand($this->mainCommand);
	}
	
	protected function onFlush(){
		// This works unless the List feature gets removed, will fixes in the future
		$cache = $this->options['styxPath'].'/Source/Cache/'.$this->Configuration['prefix'].'/Cache/List.txt';
		
		if(file_exists($cache)) unlink($cache);
		
		$this->message('Cache flushed.');
	}
	
	protected function onCreate(){
		if(empty($this->commands[1])) $this->stop('No module-name given');
		
		$module = ucfirst($this->commands[1]);
		
		// Start ModuleClass content
		$config = array();
		if($this->options['table'])
			$config['table'] = $this->options['table'];
		if($this->options['identifier'])
			$config['identifier'] = $this->options['identifier'];
		elseif($this->options['identifier.internal'] || $this->options['identifier.external'])
			$config['identifier'] = array(
				'internal' => $this->options['identifier.internal'],
				'external' => $this->options['identifier.external'],
			);
		
		$moduleBody = count($config) ? $this->createClassFunctionCode('onInitialize', array(
				'visibility' => 'protected',
				'body' => 'return '.$this->createArrayCode($config, array('indentation' => 2)),
			)) : null;
		// End ModuleClass content
		
		foreach(array(
			$this->ProjectPath.'/Layers/'.$module.'Layer.php' => $this->createFileCode($module.'Layer', array('extends' => 'Layer')),
			$this->ProjectPath.'/Models/'.$module.'Model.php' => $this->createFileCode($module.'Model', array('extends' => 'Model')),
			$this->ProjectPath.'/Modules/'.$module.'Module.php' => $this->createFileCode($module.'Module', array('extends' => 'Module', 'body' => $moduleBody)),
			$this->ProjectPath.'/Objects/'.$module.'Object.php' => $this->createFileCode($module.'Object', array('extends' => 'Object')),
		) as $file => $content){
			touch($file);
			file_put_contents($file, $content);
		}
		
		$templates = $this->ProjectPath.'/Templates/Layers/'.$module;
		if(!is_dir($templates)) mkdir($templates);
		
		$this->message('Module '.$module.' successfully created.');
	}
	
	protected function onDestroy(){
		if(empty($this->commands[1])) $this->stop('No module-name given');
		
		$module = ucfirst($this->commands[1]);
		
		if(!file_exists($this->ProjectPath.'/Modules/'.$module.'Module.php'))
			$this->stop('Module '.$module.' not found');
		
		foreach(array(
			$this->ProjectPath.'/Layers/'.$module.'Layer.php',
			$this->ProjectPath.'/Models/'.$module.'Model.php',
			$this->ProjectPath.'/Modules/'.$module.'Module.php',
			$this->ProjectPath.'/Objects/'.$module.'Object.php',
			$this->ProjectPath.'/Templates/Layers/'.$module,
		) as $file)
			$this->unlink($file);
		
		$this->message('Module '.$module.' destroyed.');
	}
	
	protected function readConfiguration($file, $type = null){
		include($file);
		$this->Configuration = ($type && !empty($CONFIGURATION[$type]) ? $CONFIGURATION[$type] : reset($CONFIGURATION));
	}
	
	protected function invokeCommand($command){
		$event = $command ? 'on'.ucfirst($command) : null;
		
		if(!$event || !method_exists($this, $event)) $this->stop('Command '.$command.' not found');
		
		$this->{$event}();
	}
	
	protected function setOptions($args){
		$options = array();
		
		array_shift($args);
		foreach($args as $arg)
			if(preg_match('/\-\-([A-z0-9\.\-]+)\=?(.+)?/', $arg, $value) && !empty($value[1]) && array_key_exists($value[1], $this->options))
				$options[$value[1]] = !empty($value[2]) ? $value[2] : null;
			elseif(preg_match('/^([A-z0-9]+)$/', $arg, $value) && !empty($value[1]))
				$this->commands[] = $value[1];
		
		$this->options = array_merge($this->options, $options);
		$this->mainCommand = !empty($this->commands[0]) ? $this->commands[0] : null;
	}
	
	protected function createFileCode($class, $options = array()){
		$options = array_merge(array(
			'extends' => null,
			'body' => null,
		), $options);
		
		return "<?php\n\nclass ".$class.($options['extends'] ? " extends ".$options['extends'] : "")." {\n\t\n\t".$options['body']."\n\t\n}";
	}
	
	protected function createClassFunctionCode($name, $options = array()){
		$options = array_merge(array(
			'visibility' => 'public',
			'static' => false,
			'body' => null,
		), $options);
		
		return $options['visibility'].($options['static'] ? ' static' : '')." function ".$name."(){\n\t\t".$options['body']."\n\t}";
	}
	
	protected function createArrayCode($array, $options = array()){
		$options = array_merge(array(
			'indentation' => 0,
			'end' => ';',
		), $options);
		
		$indent = "\n".str_repeat("\t", $options['indentation']);
		
		$code = "array(";
		
		foreach($array as $key => $value){
			if($value === null) continue;
			
			$codeValue = is_array($value) ? $this->createArrayCode($value, array_merge($options, array('indentation' => $options['indentation']+1, 'end' => null))) : "'".$value."'";
			$code .= $indent."\t'".$key."' => ".$codeValue.",";
		}
		
		return $code.$indent.')'.$options['end'];
	}
	
	protected function unlink($file){
		$file = realpath($file);
		if(is_dir($file)){
			$files = glob($file.'/*');
			if(is_array($files))
				foreach($files as $f)
					$this->unlink($f);
				
			rmdir($file);
		}else{
			if(file_exists($file)) unlink($file);
		}
	}
	
	protected function stop($message){
		die('> '.$message."\n");
	}
	
	protected function message($message){
		echo '+ '.$message."\n";
	}
	
}

$cli = new StyxCommandLine();
$cli->invoke($argv);