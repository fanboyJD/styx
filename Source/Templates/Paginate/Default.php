<?php
$pagination = $this->paginate();

$title = !empty($this->get[$this->event]) ? Data::sanitize($this->get[$this->event]) : null;

if($next = $pagination->getNext()){
	echo '<a class="next" href="'.$this->link($title, $this->event, array(
			'start' => $next,
		), true).'">${lang.paginate.next}</a>';
}

if(($previous = $pagination->getPrevious())!==false){
	echo '<a class="previous" href="'.$this->link($title, $this->event, array(
			'start' => $previous,
		), true).'">${lang.paginate.previous}</a>';
}
?>