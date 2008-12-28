<?php
$pagination = $this->paginate();

$title = !empty($this->get[$this->event]) ? Data::sanitize($this->get[$this->event]) : null;
$key = $pagination->getKey();

if($next = $pagination->getNext()){
	echo '<a class="next" href="'.$this->link($title, $this->event, array(
			$key => $next,
		), true).'">${lang.paginate.next}</a>';
}

if(($previous = $pagination->getPrevious())!==false){
	echo '<a class="previous" href="'.$this->link($title, $this->event, array(
			$key => $previous,
		), true).'">${lang.paginate.previous}</a>';
}
?>