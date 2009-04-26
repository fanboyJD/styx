<?php

class PageModel extends DatabaseModel {
	
	public function findMenuEntries(){
		return $this->findMany(array(
			'fields' => 'title, pagetitle',
			'limit' => 0,
			'order' => 'id ASC',
		));
	}
	
}