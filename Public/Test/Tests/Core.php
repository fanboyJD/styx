<?php

include('./Initialize.php');

class CoreTest extends UnitTestCase {
	
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
		$layer = Core::retrieve('app.path').'Layers';
		touch($layer.'/temp.tmp');
		mkdir($layer.'/test');
		touch($layer.'/test/temp.html');
		touch($layer.'/test/test.php');
		
		foreach(new PHPExtensionFilter(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($layer))) as $file)
			$files[] = $file->getFileName();
		
		$this->assertEqual(count($files), 3);
		
		foreach($files as $file)
			$this->assertEqual(pathinfo($file, PATHINFO_EXTENSION), 'php');
		
		unlink($layer.'/temp.tmp');
		unlink($layer.'/test/temp.html');
		unlink($layer.'/test/test.php');
		rmdir($layer.'/test');
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
		
		$this->assertTrue(Core::classExists('TestClass'));
		
		$this->assertFalse(Core::classExists('RandomClassDoesNotExist'));
	}
	
	public function testAutoload(){
		$this->assertFalse(class_exists('TestClass', false));
		
		new TestClass();
		
		$this->assertTrue(class_exists('TestClass', false));
	}
	
}