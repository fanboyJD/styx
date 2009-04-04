<?php

class NewsModule extends Module {
	
	protected function onStructureCreate(){
		return array(
			'title' => array(
				':caption' => Lang::retrieve('title'),
				':public' => true,
				':validate' => array(
					'sanitize' => true,
					'notempty' => true,
				),
			),
			'content' => array(
				':caption' => Lang::retrieve('text'),
				':element' => 'TextArea',
				':public' => true,
				':validate' => array(
					'purify' => true,
					'notempty' => true,
				),
			),
			'id' => array(),
			'uid' => array(),
			'pagetitle' => array(),
			'time' => array(),
		);
	}
	
}