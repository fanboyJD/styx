<?php

class NewsModel extends DatabaseModel {
	
	protected function initialize(){
		return array(
			
		);
	}
	
	public function getLatestNews(){
		return $this->select()->fields($this->table.'.*, users.name')->join('uid=users.id', 'users', 'left')->order('time DESC')->limit(0);
	}
	
}