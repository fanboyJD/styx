<?php

require_once('./Initialize.php');

class CoreTest extends UnitTestCase {
	
	public $layer = '';
	
	public function setUp(){
		$appath = Core::retrieve('app.path');
		
		$this->layer = $appath.'Layers';
		
		touch($this->layer.'/temp.php.tmp');
		touch($this->layer.'/temp.tpl');
		if(!file_exists($this->layer.'/test')) mkdir($this->layer.'/test');
		touch($this->layer.'/test/temp.html');
		touch($this->layer.'/test/test.php');
	}
	
	public function tearDown(){
		unlink($this->layer.'/temp.php.tmp');
		unlink($this->layer.'/temp.tpl');
		unlink($this->layer.'/test/temp.html');
		unlink($this->layer.'/test/test.php');
		rmdir($this->layer.'/test');
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
		foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->layer))) as $file){
			$this->assertEqual(pathinfo($file->getFileName(), PATHINFO_EXTENSION), 'php');
			$files[] = $file->getFileName();
		}
		
		$this->assertEqual(count($files), 4);
			
		foreach(new ExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->layer)), array('html', 'tpl')) as $file)
			$this->assertTrue(in_array(pathinfo($file->getFileName(), PATHINFO_EXTENSION), array('html', 'tpl')));
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
	
}