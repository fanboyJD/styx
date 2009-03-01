<?php

require_once('./Initialize.php');

class UserObject extends Object {
	
	public function initialize(){
		return array(
			'structure' => array(
				'id' => array(
					':caption' => 'Id',
					':validate' => array(
						'id' => true,
					),
				),
				'name' => array(
					':caption' => 'Username',
					':validate' => array(
						'pagetitle' => true,
						'notempty' => true,
					),
				),
				'job' => array(
					':caption' => 'Job description',
					':validate' => array(
						'sanitize' => true,
					),
				),
			),
		);
	}
	
}

class ObjectTest extends UnitTestCase {
	
	public function testUserObject(){
		$user = new UserObject();
		
		$user->store(array(
			'id' => 1,
			'name' => 'Admin',
			'job' => 'Server Administrator',
		));
		
		$this->assertEqual($user['id'], 1);
		$this->assertEqual($user['name'], 'Admin');
		
		$this->assertFalse(isset($user['blah']));
		$this->assertTrue(isset($user['id']));
		$this->assertFalse(empty($user['id']));
		
		$user['id'] = 5;
		
		$this->assertEqual($user['id'], 5);
		
		$user['blah'] = 'Testvalue should not be set';
		$this->assertEqual(count($user), 3);
		$this->assertNull($user['blah']);
	}
	
	public function testSave(){
		$user = new UserObject();
		
		$user->store(array(
			'id' => 'Test',
			'name' => 'Admin',
			'job' => 'Server Administrator',
		));
		
		$exception = false;
		try{
			// Should throw an exception because id can't be 'Test'
			$user->save();
		}catch(ValidatorException $e){
			$exception = true;
		}
		$this->assertTrue($exception);
		
		$user = new UserObject(array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '  >Sanitized<  ',
		));
		
		$user->save();
		
		// Job should now be clean
		$this->assertEqual($user['job'], '&gt;Sanitized&lt;');
	}

	public function testToArray(){
		$user = new UserObject(array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '  >Sanitized<  ',
		));
		
		$this->assertEqual($user->toArray(), array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '  >Sanitized<  ',
		));
		
		$user->save();
		
		$this->assertEqual($user->toArray(), array(
			'id' => 1,
			'name' => 'Admin',
			'job' => '&gt;Sanitized&lt;',
		));
	}
	
	public function testNewsObject(){
		$news = new NewsObject(Database::select('news')->where(array('id' => 1))->fetch(), false);
		
		$this->assertEqual($news->getTable(), 'news');
		
		$this->assertEqual($news['title'], 'Newsheadline');
		
		$newEntry = new NewsObject(array(
			'uid' => 1,
			'title' => 'This is some interesting title',
		));
		
		$exception = false;
		try{
			// Should throw an exception because "content" is empty
			$newEntry->save();
		}catch(ValidatorException $e){
			$exception = true;
		}
		$this->assertTrue($exception);
		
		$newEntry['content'] = '<b Malicious HTML';
		
		$newEntry->save();
		// The content should have been cleaned
		$this->assertEqual($newEntry['content'], '<b></b>');
		
		// We don't know the id but it's gotta be greater than 0
		$this->assertTrue($newEntry['id'] > 0);
		$id = $newEntry['id'];
		$newEntry->delete();
		$this->assertNull($newEntry['title']);
		
		// Double Check if it really is gone
		$this->assertFalse(Database::select('news')->where(array('id' => $id))->fetch());
	}
	
}