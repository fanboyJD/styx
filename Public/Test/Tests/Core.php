<?php

require_once('./Initialize.php');

class CoreTest extends UnitTestCase {
	
	public $folder;
	
	public function setUp(){
		$this->folder = Core::retrieve('app.public').'Temp';
		
		if(!is_dir($this->folder)) mkdir($this->folder);
		
		touch($this->folder.'/temp.tpl.tmp');
		touch($this->folder.'/temp.tpl');
		if(!is_dir($this->folder.'/test')) mkdir($this->folder.'/test');
		touch($this->folder.'/test/temp.html');
		touch($this->folder.'/test/test.html');
		touch($this->folder.'/test/test.tpl');
		touch($this->folder.'/test/test.php');
	}
	
	public function tearDown(){
		unlink($this->folder.'/temp.tpl.tmp');
		unlink($this->folder.'/temp.tpl');
		unlink($this->folder.'/test/temp.html');
		unlink($this->folder.'/test/test.html');
		unlink($this->folder.'/test/test.tpl');
		unlink($this->folder.'/test/test.php');
		rmdir($this->folder.'/test');
	}
	
	public function testPick(){
		$this->assertNull(pick(null));
		
		$this->assertNull(pick(false));
		
		$this->assertFalse(pick(null, false));
		
		$this->assertNull(pick(false, null));
		
		$this->assertTrue(pick(true));
		
		$this->assertEqual(pick(false, array()), array());
		
		$this->assertEqual(pick('a', 'b'), 'a');
		
		$this->assertEqual(pick(false, 'b'), 'b');
		
		$this->assertEqual(pick(null, 'b'), 'b');
		
		$this->assertEqual(pick('', 'b'), 'b');
		
		$this->assertEqual(pick(0, 'b'), 'b');
	}
	
	public function testExtensionFilter(){
		foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Core::retrieve('app.path').'/Layers'))) as $file){
			$this->assertEqual(pathinfo($file->getFileName(), PATHINFO_EXTENSION), 'php');
			$files[] = $file->getFileName();
		}
		
		$this->assertEqual(count($files), 4);
		
		$i = 0;
		foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->folder)), array('html', 'tpl')) as $file){
			$this->assertTrue(in_array(pathinfo($file->getFileName(), PATHINFO_EXTENSION), array('html', 'tpl')));
			$i++;
		}
		
		$this->assertEqual($i, 4);
	}
	
	public function testConfigurationUnchanged(){
		include(Core::retrieve('app.path').'/Config/Configuration.php');
		
		/* app.link got an / added */
		$this->assertEqual($CONFIGURATION['debug']['app.link'].'/', Core::retrieve('app.link'));
		
		unset($CONFIGURATION['debug']['app.link']);
		
		/* Everything else should be the same */
		foreach($CONFIGURATION['debug'] as $key => $value)
			$this->assertEqual($value, Core::retrieve($key));
	}
	
	public function testGetMethods(){
		$methods = Core::getMethods('indexlayer');
		
		$this->assertEqual($methods, array('save', 'edit', 'delete', 'view'));
	}
	
	public function testClassExists(){
		$this->assertTrue(Core::classExists('IndexLayer'));
		
		$this->assertTrue(Core::classExists('Core'));
		
		$this->assertTrue(Core::classExists('JavaScriptPacker'));
		
		$this->assertTrue(Core::classExists('Application')); // Custom User Class
		
		$this->assertFalse(Core::classExists('RandomClassDoesNotExist'));
	}
	
	public function testAutoload(){
		$this->assertFalse(class_exists('JavaScriptPacker', false)); // We know that this class won't be around at that time
		
		new JavaScriptPacker('some_random_script();');
		
		$this->assertTrue(class_exists('JavaScriptPacker', false));
	}
	
	public function testStorage(){
		Core::store('hello', 'world');
		
		$this->assertEqual(Core::retrieve('hello'), 'world');
		
		Core::store('hello', null);
		
		$this->assertNull(Core::retrieve('hello'));
		
		$this->assertEqual(Core::retrieve('hello', 'world'), 'world');
		
		Core::store(array(
			'key' => 'value',
			'some' => 'test',
		));
		
		$this->assertEqual(Core::retrieve('key'), 'value');
		
		$this->assertEqual(Core::retrieve('some', 'random'), 'test');
		
		$this->assertEqual(Core::fetch('key', 'some', 'undefined'), array('key' => 'value', 'some' => 'test', 'undefined' => null));
		
		Core::store(array(
			'key' => null,
			'hello' => null,
			'some' => null,
		));
	}
	
}