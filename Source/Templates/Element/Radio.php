<label>${:caption}${:add}</label>
<div id="${id}">
<?php
foreach($this->options[':elements'] as $val)
	echo '<label><input value="'.$val['value'].'"'.$this->implode(array('skipValue', 'skipId'), $val).' '.($val['value']==$this->options['value'] ? 'checked="checked" ' : '').'/> '.$val[':add'].$val[':caption'].'</label>';
?>
</div>