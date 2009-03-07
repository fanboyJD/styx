<?php

class NewsModel extends DatabaseModel {
	
	public function getLatestNews($limit = 0){
		return array(
			'fields' => $this->table.'.*, users.name',
			'join' => array('uid=users.id', 'users', 'left'),
			'order' => 'time DESC',
			'limit' => Data::id($limit),
		);
	}
	
}