<?php

require_once('./Initialize.php');

class NewsTestObject extends DatabaseObject {}

class ModelTest extends StyxUnitTest {
	
	public function testNewsModel(){
		$news = new NewsModel();
		$myNewsEntry = $news->findByIdentifier('Test');
		
		$this->assertTrue($myNewsEntry instanceof NewsObject);
		$this->assertEqual($myNewsEntry['id'], 2);
		$this->assertFalse($myNewsEntry->isNew());
	}
	
	public function testCreateModel(){
		$newsModel = Model::create('News')->find(array(
			'where' => array('id' => 1),
		));
		
		$this->assertEqual($newsModel['id'], 1);
	}
	
	public function testFind(){
		$newsModel = Model::create('News');
		$newsModel->find(array(
			'where' => array('id' => 1),
		));
		
		$count = 0;
		foreach($newsModel as $newsEntry){
			$this->assertTrue($newsEntry instanceof NewsObject);
			$count++;
		}
		$this->assertEqual($count, 1);
	}
	
	public function testFindMany(){
		$newsModel = Model::create('News');
		$newsModel->findMany();
		
		$count = 0;
		foreach($newsModel as $newsEntry){
			$this->assertTrue($newsEntry instanceof NewsObject);
			$count++;
		}
		$this->assertEqual($count, 3);
	}
	
	public function testMake(){
		$newsModel = Model::create('News');
		$newsEntry = $newsModel->make(array(
			'id' => 1,
			'title' => 'Test',
		));
		
		$this->assertTrue($newsEntry instanceof NewsObject);
		$this->assertEqual($newsEntry['id'], 1);
		
		// Make returns the instance if it already is instanceof NewsObject
		$newsEntry2 = $newsModel->make($newsEntry);
		$this->assertTrue($newsEntry2 instanceof NewsObject);
		$this->assertEqual($newsEntry, $newsEntry2);
		
		// Converts instances not type NewsObject
		$newsEntry3 = new NewsTestObject(array(
			'id' => 1,
			'title' => 'Test',
		));
		$newsEntry4 = $newsModel->make($newsEntry3);
		$this->assertTrue($newsEntry4 instanceof NewsObject);
		$this->assertNotEqual($newsEntry3, $newsEntry4);
	}
	
}