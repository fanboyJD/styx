<?php
$pagination = $this->paginate();

$layer = pick($pagination->getLink(), $this);
$title = !empty($this->get[$this->event]) ? Data::sanitize($this->get[$this->event]) : $pagination->getTitle();
$key = $pagination->getKey();

if($next = $pagination->getNext()){
	$options = $pagination->getLinkOptions();
	echo '<a class="next" href="'.$layer->link($title, $this->event, Hash::extend($options, array(
			$key => $next,
		)), true).'">${lang.paginate.next}</a>';
}

if(($previous = $pagination->getPrevious())!==false){
	$options = $pagination->getLinkOptions();
	echo '<a class="previous" href="'.$layer->link($title, $this->event, Hash::extend($options, array(
			$key => $previous,
		)), true).'">${lang.paginate.previous}</a>';
}
?>