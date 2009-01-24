<?php

require_once('./Initialize.php');

class CacheTest extends UnitTestCase {
	
	public function testEngine(){
		Cache::getEngine('apc')->store('test', true);
		
		$this->assertTrue(Cache::getEngine('apc')->retrieve('test'));
		
		$apc = Cache::getEngine('apc');
		
		$apc->erase(array('test'));
		
		// Should be either null or false, depending on what the engine returns
		$this->assertTrue(in_array($apc->retrieve('test'), array(null, false)));
	}
	
	public function testCache(){
		$c = Cache::getInstance();
		
		$c->store('Test/Cache', 'random data');
		$this->assertEqual($c->retrieve('Test/Cache'), 'random data');
		$c->erase('Test/Cache');
		$this->assertNull($c->retrieve('Test/Cache'));
		
		$c->store('Test/Cache', 'random data', array('type' => 'memcache'));
		
		$this->assertEqual($c->retrieve('Test/Cache'), 'random data');
		$c->erase('Test/Cache');
		$c->erase('Test/Cache');
		$this->assertNull($c->retrieve('Test/Cache'));
		
		$c->store('Test/Cache', 'random data', array('type' => 'eaccelerator'));
		$this->assertEqual($c->retrieve('Test/Cache'), 'random data');
		$c->erase('Test/Cache');
		$this->assertNull($c->retrieve('Test/Cache'));
		
		$array = array(
			array('id' => 1, 'title' => 'C'),
			array('id' => 2, 'title' => 'D', 'test' => 2),
		);
		
		$c->store('Some/Cache', $array);
		$this->assertEqual($c->retrieve('Some/Cache'), $array);
		$c->erase('Some/Cache');
		$this->assertNull($c->retrieve('Some/Cache'));
	}
	
	public function testTags(){
		$c = Cache::getInstance();
		
		$c->store('Test/Cache', 'random data', array('tags' => array('random', 'tag')));
		$c->eraseByTag('random');
		
		$this->assertNull($c->retrieve('Test/Cache'));
	}
	
	public function testEraseBy(){
		$c = Cache::getInstance();
		
		$c->store('Test/Cache', 'random data');
		$c->eraseBy('Test');
		
		$this->assertNull($c->retrieve('Test/Cache'));
	}
	
	public function testEraseAll(){
		$c = Cache::getInstance();
		
		$c->store('Test/Cache', 'random data');
		$c->eraseAll();
		
		$this->assertNull($c->retrieve('Test/Cache'));
	}
	
	public function testTime(){
		$link = rtrim(Request::getUrl(), '/').'/';
		// This sets the cache for 1 second
		$b = new SimpleBrowser();
		$b->get($link.'Helper/Cache.php');
		
		sleep(1);
		
		// If the cache is not empty it should output "1"
		$b = new SimpleBrowser();
		$b->get($link.'Helper/Cache.php?check=true');
		
		$this->assertEqual($b->getContent(), 1);
		
		sleep(2);
		
		// If the cache is empty it should output "0"
		$b = new SimpleBrowser();
		$b->get($link.'Helper/Cache.php?check=true');
		
		$this->assertEqual($b->getContent(), 0);
	}
	
}