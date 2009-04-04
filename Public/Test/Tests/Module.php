<?php

require_once('./Initialize.php');

class MyModule extends Module {
	
	protected function onInitialize(){
		return array(
			'model' => 'news',
			'object' => 'user',
		);
	}
	
	protected function onStructureCreate(){
		return array(
			'id' => array(),
		);
	}
	
}

class ModuleTest extends StyxUnitTest {
	
	public function testModule(){
		$module = Module::retrieve('My');
		
		$this->assertEqual($module->getStructure(), array('id' => array()));
		
		$this->assertEqual($module->getName(), 'my');
		$this->assertEqual($module->getName('object'), 'user');
		$this->assertEqual($module->getName('model'), 'news');
	}
	
	public function testNewsModule(){
		$module = Module::retrieve('News');
		
		$this->assertTrue($module instanceof NewsModule);
		$this->assertTrue($module->getModel() instanceof NewsModel);
		
		$this->assertEqual($module->getName(), 'news');
		$this->assertEqual($module->getName('object'), 'news');
		$this->assertEqual($module->getName('model'), 'news');
	}
	
}