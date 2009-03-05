<?php

class PageModel extends DatabaseModel {
	
	public function findMenuEntries(){
		return $this->makeMany($this->select($this->table)->fields('title, pagetitle')->limit(0)->order('id ASC'));
	}
	
}