<label>${:caption}${:add}</label>
<div id="${id}">
<?php
foreach($this->options[':elements'] as $val)
	echo '<label><input value="'.$val['value'].'"'.$this->implode(array('skipValue', 'skipId'), $val).' '.($val['value']==$this->options['value'] ? 'checked="checked" ' : '').' /> '.$val[':caption'].(!empty($val[':add']) ? $val[':add'] : '').'</label>';
?>
</div>