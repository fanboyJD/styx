<?php
/*
 * Styx::QueryCache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Extends the QuerySelect Class to automatically Cache the fetched data
 *
 */

class QueryCache extends QuerySelect {
	
	private function getIdentifier($type = null){
		return $this->table.'_'.md5($this->format().$type);
	}
	
	private function getCache($type = null){
		$cache = Cache::getInstance()->retrieve('QueryCache/'.$this->table.'/'.md5($this->format().$type));
		return $cache=='empty' ? false : $cache;
	}
	
	private function setCache($content, $type = null){
		$options = array(
			'ttl' => ONE_DAY/2,
		);
		$data = $this->Storage->retrieve('join');
		if(is_array($data) && !empty($data['table']))
			$options['tags'] = array('Db/'.$data['table']);
		
		Cache::getInstance()->store('QueryCache/'.$this->table.'/'.md5($this->format().$type), $content ? $content : 'empty', $options);
		return $content;
	}
	
	public function fetch($type = null){
		$this->Storage->retrieve('limit', array(0, 1)); // To overcome big queries
		
		return ($cache = $this->getCache($type)) ? $cache : $this->setCache(parent::fetch($type), $type);
	}
	
	public function retrieve($type = null){
		$this->queried = false;
		
		return ($cache = $this->getCache($type)) ? $this->Data = $cache : $this->setCache(parent::retrieve($type), $type);
	}
	
}