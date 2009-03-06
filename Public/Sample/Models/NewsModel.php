<?php

class NewsModel extends DatabaseModel {
	
	public function findLatestNews(){
		return $this->select()->fields($this->table.'.*, users.name')->join('uid=users.id', 'users', 'left')->order('time DESC')->limit(0);
	}
	
}