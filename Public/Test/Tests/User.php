<?php

require_once('./Initialize.php');

class UserTest extends UnitTestCase {
	
	public function testRights(){
		User::store(array(
			'id' => 1,
			'name' => 'admin',
			'pwd' => '0d0de95795a741adbe0ac22d788ef01d4d1ea226',
			'session' => sha1(mt_rand(0, 10000)),
		));
		
		User::setRights('layer.index');
		
		$this->assertTrue(User::hasRight('layer.index'));
		
		$this->assertTrue(User::hasRight('layer.index.edit'));
		
		$this->assertFalse(User::hasRight('layer.index1'));
		
		$this->assertFalse(User::hasRight('layer.page'));
		
		User::removeRight('layer');
		
		$this->assertFalse(User::hasRight('layer.index'));
		
		$this->assertFalse(User::hasRight('layer.index.edit'));
		
		User::setRights('layer.index', 'layer.index.edit', 'layer.page');
		
		User::removeRight('layer.index');
		
		$this->assertFalse(User::hasRight('layer.index.edit'));
		
		$this->assertTrue(User::hasRight('layer.page.edit'));
		
		User::addRight('custom.right');
		
		$this->assertTrue(User::hasRight('custom.right'));
		
		User::store(false);
		
		$this->assertFalse(User::hasRight('layer.page.edit'));

	}
	
}