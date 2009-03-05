<?php

class NewsModel extends DatabaseModel {
	
	protected function initialize(){
		return array(
			
		);
	}
	
	public function findLatestNews(){
		return $this->select()->fields($this->table.'.*, users.name')->join('uid=users.id', 'users', 'left')->order('time DESC')->limit(0);
	}
	
}