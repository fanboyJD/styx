<?php

require_once('./Initialize.php');

class StorageTest extends UnitTestCase {
	
	public function testStorage(){
		$Storage = new Storage();
		
		$Storage->store('hello', 'world');
		
		$this->assertEqual($Storage->retrieve('hello'), 'world');
		
		$Storage->erase('hello');
		
		$this->assertNull($Storage->retrieve('hello'));
		
		$this->assertEqual($Storage->retrieve('hello', 'world'), 'world');
		
		$Storage->store(array(
			'key' => 'value',
			'some' => 'test',
		));
		
		$this->assertEqual($Storage->retrieve('key'), 'value');
		
		$this->assertEqual($Storage->retrieve('some', 'random'), 'test');
	}
	
}